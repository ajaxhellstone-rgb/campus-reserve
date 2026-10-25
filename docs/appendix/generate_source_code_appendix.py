from __future__ import annotations

import subprocess
import textwrap
from collections import defaultdict
from pathlib import Path

from docx import Document
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.shared import Inches, Pt, RGBColor
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from reportlab.lib import colors
from reportlab.lib.pagesizes import landscape, letter
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate,
    Frame,
    Flowable,
    PageBreak,
    PageTemplate,
    Paragraph,
    Spacer,
)
from xml.sax.saxutils import escape


ROOT = Path(__file__).resolve().parents[2]
OUTPUT = Path(__file__).resolve().parent / "Campus_Reserve_Source_Code_Appendix.docx"
PDF_OUTPUT = Path(__file__).resolve().parent / "Campus_Reserve_Source_Code_Appendix.pdf"
SOURCE_ROOTS = (
    "app/",
    "bootstrap/",
    "config/",
    "database/",
    "docker/",
    "public/",
    "resources/",
    "routes/",
    "tests/",
)
ROOT_FILES = {
    ".env.example",
    "Dockerfile",
    "artisan",
    "composer.json",
    "package.json",
    "phpunit.xml",
    "vite.config.js",
}
SOURCE_EXTENSIONS = {
    ".conf",
    ".css",
    ".example",
    ".htaccess",
    ".js",
    ".json",
    ".php",
    ".sh",
    ".xml",
    ".yaml",
    ".yml",
}
EXCLUDED_PARTS = {"build", "cache", "node_modules", "output", "storage", "vendor"}


class CodeLine(Flowable):
    def __init__(self, text: str, font_name: str, font_size: float, leading: float) -> None:
        super().__init__()
        self.text = text
        self.font_name = font_name
        self.font_size = font_size
        self.leading = leading

    def wrap(self, available_width: float, available_height: float) -> tuple[float, float]:
        self.width = available_width
        self.height = self.leading
        return self.width, self.height

    def draw(self) -> None:
        self.canv.setFont(self.font_name, self.font_size)
        self.canv.setFillColor(colors.HexColor("#202124"))
        self.canv.drawString(0, 1, self.text)


class SourceCodePdf(BaseDocTemplate):
    def afterFlowable(self, flowable: Flowable) -> None:
        level = getattr(flowable, "outline_level", None)
        key = getattr(flowable, "bookmark_key", None)
        if level is None or key is None:
            return

        self.canv.bookmarkPage(key)
        self.canv.addOutlineEntry(flowable.getPlainText(), key, level=level, closed=level == 0)


def add_pdf_heading(
    story: list[Flowable],
    text: str,
    style: ParagraphStyle,
    bookmark_key: str,
    outline_level: int,
) -> None:
    heading = Paragraph(escape(text), style)
    heading.bookmark_key = bookmark_key
    heading.outline_level = outline_level
    story.append(heading)


def add_code_to_pdf(
    story: list[Flowable],
    relative_path: Path,
    heading_style: ParagraphStyle,
    font_name: str,
) -> None:
    story.append(Spacer(1, 4))
    heading = Paragraph(escape(relative_path.as_posix()), heading_style)
    heading.bookmark_key = f"source-file-{len(story)}"
    heading.outline_level = 1
    story.append(heading)

    content = (ROOT / relative_path).read_text(encoding="utf-8", errors="replace")
    lines = content.splitlines() or [""]
    for number, line in enumerate(lines, start=1):
        prefix = f"{number:>4}  "
        wrapped = textwrap.wrap(
            line.expandtabs(4),
            width=180,
            initial_indent=prefix,
            subsequent_indent="      ",
            break_long_words=True,
            break_on_hyphens=False,
            drop_whitespace=False,
            replace_whitespace=False,
        ) or [prefix]
        story.extend(CodeLine(part, font_name, 5.8, 7.2) for part in wrapped)


def generate_pdf(files: list[Path], commit: str) -> None:
    import reportlab

    font_path = Path("C:/Windows/Fonts/consola.ttf")
    if not font_path.is_file():
        font_path = Path(reportlab.__file__).parent / "fonts" / "VeraMono.ttf"
    pdfmetrics.registerFont(TTFont("CampusCode", str(font_path)))

    page_width, page_height = landscape(letter)
    document = SourceCodePdf(
        str(PDF_OUTPUT),
        pagesize=(page_width, page_height),
        leftMargin=34,
        rightMargin=34,
        topMargin=42,
        bottomMargin=32,
        title="Campus Reserve Source Code Appendix",
        author="Campus Reserve",
    )
    frame = Frame(
        document.leftMargin,
        document.bottomMargin,
        document.width,
        document.height,
        id="source-code",
        leftPadding=0,
        rightPadding=0,
        topPadding=0,
        bottomPadding=0,
    )

    def draw_page(canvas, doc) -> None:
        canvas.saveState()
        canvas.setFillColor(colors.HexColor("#14532d"))
        canvas.setFont("Helvetica-Bold", 8)
        canvas.drawString(34, page_height - 24, "CAMPUS RESERVE  |  SOURCE CODE APPENDIX")
        canvas.setFillColor(colors.HexColor("#64748b"))
        canvas.setFont("Helvetica", 8)
        canvas.drawRightString(page_width - 34, 18, f"Page {doc.page}")
        canvas.restoreState()

    document.addPageTemplates(PageTemplate(id="source-code", frames=frame, onPage=draw_page))

    styles = getSampleStyleSheet()
    pdf_styles = {
        "title": ParagraphStyle(
            "AppendixTitle",
            parent=styles["Title"],
            fontName="Helvetica-Bold",
            fontSize=24,
            leading=30,
            textColor=colors.HexColor("#14532d"),
            alignment=1,
            spaceAfter=12,
        ),
        "section": ParagraphStyle(
            "SourceGroup",
            parent=styles["Heading1"],
            fontName="Helvetica-Bold",
            fontSize=16,
            leading=20,
            textColor=colors.HexColor("#14532d"),
            spaceBefore=8,
            spaceAfter=6,
            keepWithNext=True,
        ),
        "file": ParagraphStyle(
            "SourceFile",
            parent=styles["Heading2"],
            fontName="Helvetica-Bold",
            fontSize=9,
            leading=12,
            textColor=colors.HexColor("#14532d"),
            spaceBefore=7,
            spaceAfter=2,
            keepWithNext=True,
        ),
        "body": ParagraphStyle(
            "AppendixBody",
            parent=styles["BodyText"],
            fontName="Helvetica",
            fontSize=9,
            leading=13,
            spaceAfter=7,
        ),
    }
    story: list[Flowable] = [
        Spacer(1, 100),
        Paragraph("APPENDIX A", pdf_styles["title"]),
        Paragraph("Campus Reserve Source Code Listing", pdf_styles["title"]),
        Spacer(1, 10),
        Paragraph(
            f"Generated from {len(files)} tracked, first-party source and configuration files.",
            pdf_styles["body"],
        ),
        Paragraph(f"Repository revision: {escape(commit)}", pdf_styles["body"]),
        PageBreak(),
        Paragraph("Scope and exclusions", pdf_styles["section"]),
        Paragraph(
            "This appendix provides the application source code, database migrations, "
            "views and frontend code, routes, tests, and selected deployment/configuration files.",
            pdf_styles["body"],
        ),
        Paragraph(
            "Private environment files, credentials, dependencies, runtime data, generated "
            "build output, binary assets, and unrelated documents are intentionally excluded.",
            pdf_styles["body"],
        ),
        Paragraph("Source code sections", pdf_styles["section"]),
        Paragraph(
            "Use the PDF bookmarks panel to jump directly to a project section or an individual "
            "file. File headings include their repository-relative paths; line numbers are for "
            "reference and are not part of the source code.",
            pdf_styles["body"],
        ),
        PageBreak(),
    ]

    grouped: dict[str, list[Path]] = defaultdict(list)
    for path in files:
        normalized = path.as_posix()
        group = normalized.split("/", 1)[0] if "/" in normalized else "Project root"
        grouped[group].append(path)

    group_index = 0
    for group in sorted(grouped, key=str.casefold):
        group_index += 1
        add_pdf_heading(
            story,
            group,
            pdf_styles["section"],
            f"source-section-{group_index}",
            0,
        )
        for path in grouped[group]:
            add_code_to_pdf(story, path, pdf_styles["file"], "CampusCode")

    document.build(story)


def tracked_source_files() -> list[Path]:
    tracked = subprocess.check_output(
        ["git", "ls-files", "-z"],
        cwd=ROOT,
    ).decode("utf-8").split("\0")
    selected: list[Path] = []

    for name in tracked:
        if not name or name in {".env", ".env.local"}:
            continue
        if not (name.startswith(SOURCE_ROOTS) or name in ROOT_FILES):
            continue

        path = Path(name)
        if path.suffix.lower() not in SOURCE_EXTENSIONS and name not in ROOT_FILES:
            continue
        if EXCLUDED_PARTS.intersection(path.parts):
            continue
        if path.is_file():
            selected.append(path)

    return sorted(selected, key=lambda path: path.as_posix().casefold())


def set_cell_shading(cell, fill: str) -> None:
    properties = cell._tc.get_or_add_tcPr()
    shading = OxmlElement("w:shd")
    shading.set(qn("w:fill"), fill)
    properties.append(shading)


def add_page_number(paragraph) -> None:
    paragraph.alignment = WD_ALIGN_PARAGRAPH.CENTER
    run = paragraph.add_run("Page ")
    run.font.size = Pt(9)
    field = OxmlElement("w:fldSimple")
    field.set(qn("w:instr"), "PAGE")
    paragraph._p.append(field)


def format_document(document: Document) -> None:
    section = document.sections[0]
    section.top_margin = Inches(0.65)
    section.bottom_margin = Inches(0.65)
    section.left_margin = Inches(0.7)
    section.right_margin = Inches(0.7)

    normal = document.styles["Normal"]
    normal.font.name = "Aptos"
    normal.font.size = Pt(9)

    for style_name, size in (("Heading 1", 16), ("Heading 2", 10)):
        style = document.styles[style_name]
        style.font.name = "Aptos Display"
        style.font.size = Pt(size)
        style.font.color.rgb = RGBColor(20, 83, 45)
        style.paragraph_format.keep_with_next = True

    header = section.header.paragraphs[0]
    header.text = "CAMPUS RESERVE  |  SOURCE CODE APPENDIX"
    header.style = document.styles["Caption"]
    header.alignment = WD_ALIGN_PARAGRAPH.RIGHT
    add_page_number(section.footer.paragraphs[0])


def add_code_listing(document: Document, relative_path: Path) -> None:
    heading = document.add_paragraph(style="Heading 2")
    heading.paragraph_format.space_before = Pt(8)
    heading.paragraph_format.space_after = Pt(2)
    heading.add_run(relative_path.as_posix())

    content = (ROOT / relative_path).read_text(encoding="utf-8", errors="replace")
    lines = content.splitlines()
    if not lines:
        lines = [""]

    for number, line in enumerate(lines, start=1):
        paragraph = document.add_paragraph()
        paragraph.paragraph_format.space_after = Pt(0)
        paragraph.paragraph_format.space_before = Pt(0)
        paragraph.paragraph_format.line_spacing = 1
        paragraph.paragraph_format.keep_together = False

        run = paragraph.add_run(f"{number:>4}  {line}")
        run.font.name = "Consolas"
        run.font.size = Pt(7)
        run._element.rPr.rFonts.set(qn("w:eastAsia"), "Consolas")


def main() -> None:
    files = tracked_source_files()
    if not files:
        raise RuntimeError("No tracked source files were selected for the appendix.")

    document = Document()
    format_document(document)

    title = document.add_paragraph()
    title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    title.paragraph_format.space_before = Pt(100)
    title_run = title.add_run("APPENDIX A")
    title_run.bold = True
    title_run.font.size = Pt(18)
    title_run.font.color.rgb = RGBColor(20, 83, 45)

    subtitle = document.add_paragraph()
    subtitle.alignment = WD_ALIGN_PARAGRAPH.CENTER
    subtitle_run = subtitle.add_run("Campus Reserve Source Code Listing")
    subtitle_run.bold = True
    subtitle_run.font.size = Pt(24)

    description = document.add_paragraph()
    description.alignment = WD_ALIGN_PARAGRAPH.CENTER
    description.paragraph_format.space_before = Pt(12)
    description.add_run(
        f"Generated from {len(files)} tracked, first-party source and configuration files."
    )

    commit = subprocess.check_output(
        ["git", "rev-parse", "--short", "HEAD"],
        cwd=ROOT,
        text=True,
    ).strip()
    generated = document.add_paragraph()
    generated.alignment = WD_ALIGN_PARAGRAPH.CENTER
    generated.paragraph_format.space_before = Pt(6)
    generated.add_run(f"Repository revision: {commit}")
    document.add_page_break()

    document.add_heading("Scope and exclusions", level=1)
    document.add_paragraph(
        "This appendix provides the application source code, database migrations, "
        "views and frontend code, routes, tests, and selected deployment/configuration "
        "files for review and documentation."
    )
    document.add_paragraph(
        "Private environment files (including .env), credentials, dependencies "
        "(vendor and node_modules), runtime data, generated build output, binary "
        "assets, and unrelated documents are intentionally excluded. The tracked "
        ".env.example contains configuration placeholders only."
    )

    document.add_heading("File inventory", level=1)
    grouped: dict[str, list[str]] = defaultdict(list)
    for path in files:
        normalized = path.as_posix()
        group = normalized.split("/", 1)[0] if "/" in normalized else "Project root"
        grouped[group].append(normalized)

    inventory = document.add_table(rows=0, cols=2)
    inventory.style = "Light Shading Accent 1"
    for group in sorted(grouped, key=str.casefold):
        row = inventory.add_row()
        row.cells[0].text = group
        row.cells[1].text = f"{len(grouped[group])} files"
        for cell in row.cells:
            cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER
            set_cell_shading(cell, "E8F2EB")
            for run in cell.paragraphs[0].runs:
                run.bold = True
        for path in grouped[group]:
            row = inventory.add_row()
            row.cells[0].text = path
            row.cells[1].text = ""
            row.cells[0].paragraphs[0].paragraph_format.space_after = Pt(0)

    document.add_page_break()
    document.add_heading("Source code listings", level=1)
    document.add_paragraph(
        "Listings are reproduced from the tracked repository files. Line numbers "
        "are included for reference and are not part of the source code."
    )
    for path in files:
        add_code_listing(document, path)

    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    document.save(OUTPUT)
    generate_pdf(files, commit)
    print(f"Created {OUTPUT}")
    print(f"Created {PDF_OUTPUT}")
    print(f"Included {len(files)} files from revision {commit}")


if __name__ == "__main__":
    main()
