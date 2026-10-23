import http from 'k6/http';
import { check, sleep } from 'k6';

const testType = __ENV.TEST_TYPE || 'load';
const baseUrl = (__ENV.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const isLocalTarget = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/i.test(baseUrl);

if (!isLocalTarget && __ENV.ALLOW_REMOTE !== 'true') {
  throw new Error('Remote load testing is disabled by default. Set ALLOW_REMOTE=true only for an authorized test environment.');
}

const profiles = {
  load: {
    stages: [
      { duration: '10s', target: 1 },
      { duration: '20s', target: 5 },
      { duration: '10s', target: 0 },
    ],
  },
  stress: {
    stages: [
      { duration: '10s', target: 1 },
      { duration: '15s', target: 5 },
      { duration: '15s', target: 10 },
      { duration: '10s', target: 15 },
      { duration: '15s', target: 0 },
    ],
  },
  spike: {
    stages: [
      { duration: '10s', target: 1 },
      { duration: '2s', target: 15 },
      { duration: '15s', target: 15 },
      { duration: '2s', target: 1 },
      { duration: '10s', target: 1 },
      { duration: '10s', target: 0 },
    ],
  },
  soak: {
    vus: 2,
    duration: '60s',
  },
};

const profile = profiles[testType];
if (!profile) {
  throw new Error(`Unknown TEST_TYPE "${testType}". Choose load, stress, spike, or soak.`);
}

export const options = {
  ...profile,
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1000'],
  },
};

const publicReadOnlyPaths = ['/', '/login', '/signup', '/about', '/contact', '/api/facilities'];

export default function () {
  const path = publicReadOnlyPaths[Math.floor(Math.random() * publicReadOnlyPaths.length)];
  const response = http.get(`${baseUrl}${path}`, {
    tags: { page: path },
  });

  check(response, {
    [`${path} returns 200`]: (res) => res.status === 200,
  });

  sleep(0.2);
}
