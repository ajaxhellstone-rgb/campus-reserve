<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProfilePicture;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilePicturePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploaded_profile_picture_is_stored_in_shared_database_and_available_on_other_devices(): void
    {
        $user = User::factory()->create();
        $anotherDeviceUser = User::factory()->create();
        $image = $this->fakePng('profile.png');
        $expectedContents = file_get_contents($image->getRealPath());

        $upload = $this->actingAs($user)
            ->postJson(route('profile.picture.upload'), [
                'profile_picture' => $image,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'Profile picture uploaded successfully.');

        $picture = UserProfilePicture::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('image/png', $picture->mime_type);
        $this->assertSame(base64_encode($expectedContents), $picture->image_data);
        $this->assertNull($user->fresh()->profile_picture);
        $upload->assertJsonPath('picture_url', $user->fresh()->profilePictureUrl());

        $this->actingAs($anotherDeviceUser)
            ->get(route('profile.picture.show', $user))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertContent(base64_decode($picture->image_data));
    }

    public function test_reupload_replaces_the_database_profile_picture(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('profile.picture.upload'), [
            'profile_picture' => $this->fakePng('first.png'),
        ])->assertOk();
        $firstPicture = UserProfilePicture::where('user_id', $user->id)->firstOrFail();

        $this->postJson(route('profile.picture.upload'), [
            'profile_picture' => $this->fakePng('second.png'),
        ])->assertOk();

        $picture = UserProfilePicture::where('user_id', $user->id)->firstOrFail();
        $this->assertSame($firstPicture->id, $picture->id);
        $this->assertSame('image/png', $picture->mime_type);
        $this->assertSame(1, UserProfilePicture::where('user_id', $user->id)->count());
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lWQAAAAASUVORK5CYII=', true);
        $path = tempnam(sys_get_temp_dir(), 'profile-picture-');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, 'image/png', null, true);
    }
}
