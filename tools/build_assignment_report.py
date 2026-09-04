from __future__ import annotations

from io import BytesIO
from pathlib import Path

from PIL import Image as PILImage
from reportlab.graphics.shapes import Drawing, Line, Polygon, Rect, String
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import inch, mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    BaseDocTemplate,
    Flowable,
    Frame,
    HRFlowable,
    Image,
    KeepTogether,
    PageBreak,
    PageTemplate,
    Paragraph,
    Preformatted,
    Spacer,
    Table,
    TableStyle,
)
from reportlab.platypus.tableofcontents import TableOfContents


ROOT = Path(r"C:\xampp\htdocs\project")
OUTPUT = ROOT / "output" / "pdf" / "Heng Chin Yang.pdf"
DESIGN_PATTERN_IMAGE = Path(r"C:\Users\heng\Downloads\designpattern.drawio (3).png")
DETAIL_SCREENSHOT = Path(
    r"C:\Users\heng\AppData\Local\Temp\codex-clipboard-6bc59bf9-3fdf-41a3-9d64-a82ad9e458ad.png"
)

NAVY = colors.HexColor("#123A54")
TEAL = colors.HexColor("#007A87")
PALE = colors.HexColor("#EAF4F6")
PALE_BLUE = colors.HexColor("#EDF4F8")
INK = colors.HexColor("#20313D")
MUTED = colors.HexColor("#587080")
LIGHT_LINE = colors.HexColor("#C8D7DE")
SUCCESS = colors.HexColor("#16734A")
WARNING = colors.HexColor("#925F10")
CODE_BG = colors.HexColor("#F4F6F8")


def register_fonts() -> None:
    pdfmetrics.registerFont(TTFont("Arial", r"C:\Windows\Fonts\arial.ttf"))
    pdfmetrics.registerFont(TTFont("Arial-Bold", r"C:\Windows\Fonts\arialbd.ttf"))
    pdfmetrics.registerFont(TTFont("Consolas", r"C:\Windows\Fonts\consola.ttf"))
    pdfmetrics.registerFont(TTFont("Consolas-Bold", r"C:\Windows\Fonts\consolab.ttf"))


register_fonts()


styles = getSampleStyleSheet()
styles.add(
    ParagraphStyle(
        name="ReportTitle",
        fontName="Arial-Bold",
        fontSize=25,
        leading=31,
        textColor=NAVY,
        alignment=TA_CENTER,
        spaceAfter=10,
    )
)
styles.add(
    ParagraphStyle(
        name="ReportSubtitle",
        fontName="Arial",
        fontSize=13,
        leading=18,
        textColor=TEAL,
        alignment=TA_CENTER,
        spaceAfter=10,
    )
)
styles.add(
    ParagraphStyle(
        name="H1Report",
        fontName="Arial-Bold",
        fontSize=17,
        leading=21,
        textColor=NAVY,
        spaceBefore=8,
        spaceAfter=9,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="H2Report",
        fontName="Arial-Bold",
        fontSize=12.5,
        leading=16,
        textColor=TEAL,
        spaceBefore=9,
        spaceAfter=5,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="H3Report",
        fontName="Arial-Bold",
        fontSize=10.3,
        leading=13.5,
        textColor=NAVY,
        spaceBefore=7,
        spaceAfter=4,
        keepWithNext=True,
    )
)
styles.add(
    ParagraphStyle(
        name="BodyReport",
        fontName="Arial",
        fontSize=9.4,
        leading=13.4,
        textColor=INK,
        alignment=TA_JUSTIFY,
        spaceAfter=6,
    )
)
styles.add(
    ParagraphStyle(
        name="BulletReport",
        parent=styles["BodyReport"],
        leftIndent=15,
        firstLineIndent=-8,
        bulletIndent=0,
        alignment=TA_LEFT,
        spaceAfter=3,
    )
)
styles.add(
    ParagraphStyle(
        name="Caption",
        fontName="Arial",
        fontSize=8,
        leading=10.5,
        textColor=MUTED,
        alignment=TA_CENTER,
        spaceBefore=4,
        spaceAfter=8,
    )
)
styles.add(
    ParagraphStyle(
        name="Small",
        fontName="Arial",
        fontSize=7.8,
        leading=10.4,
        textColor=INK,
        spaceAfter=3,
    )
)
styles.add(
    ParagraphStyle(
        name="Callout",
        fontName="Arial",
        fontSize=9,
        leading=13,
        textColor=NAVY,
        backColor=PALE,
        borderColor=LIGHT_LINE,
        borderWidth=0.7,
        borderPadding=9,
        spaceBefore=5,
        spaceAfter=8,
    )
)
styles.add(
    ParagraphStyle(
        name="CodeReport",
        fontName="Consolas",
        fontSize=6.8,
        leading=8.5,
        textColor=colors.HexColor("#1F2933"),
        backColor=CODE_BG,
        borderColor=LIGHT_LINE,
        borderWidth=0.5,
        borderPadding=7,
        spaceBefore=4,
        spaceAfter=4,
    )
)
styles.add(
    ParagraphStyle(
        name="ReferenceReport",
        fontName="Arial",
        fontSize=8.6,
        leading=12.2,
        leftIndent=17,
        firstLineIndent=-17,
        textColor=INK,
        spaceAfter=6,
    )
)


class NumberedReport(BaseDocTemplate):
    def __init__(self, filename: str):
        super().__init__(
            filename,
            pagesize=A4,
            leftMargin=18 * mm,
            rightMargin=18 * mm,
            topMargin=19 * mm,
            bottomMargin=18 * mm,
            title="BMIT3173 Assignment Report - Heng Chin Yang",
            author="Heng Chin Yang",
            subject="MediCare Connect Patient Record Module",
        )
        frame = Frame(
            self.leftMargin,
            self.bottomMargin,
            self.width,
            self.height,
            leftPadding=0,
            rightPadding=0,
            topPadding=0,
            bottomPadding=0,
            id="body",
        )
        self.addPageTemplates(PageTemplate(id="report", frames=[frame], onPage=self._page))

    def _page(self, canvas, doc):
        canvas.saveState()
        if doc.page > 1:
            canvas.setStrokeColor(LIGHT_LINE)
            canvas.setLineWidth(0.5)
            canvas.line(18 * mm, A4[1] - 12 * mm, A4[0] - 18 * mm, A4[1] - 12 * mm)
            canvas.setFont("Arial", 7.4)
            canvas.setFillColor(MUTED)
            canvas.drawString(18 * mm, A4[1] - 9.5 * mm, "BMIT3173 Integrative Programming")
            canvas.drawRightString(A4[0] - 18 * mm, A4[1] - 9.5 * mm, "Patient Record Module")
            canvas.line(18 * mm, 12 * mm, A4[0] - 18 * mm, 12 * mm)
            canvas.drawString(18 * mm, 8 * mm, "Heng Chin Yang | 25WMR09726")
            canvas.drawRightString(A4[0] - 18 * mm, 8 * mm, f"Page {doc.page}")
        canvas.restoreState()

    def afterFlowable(self, flowable: Flowable):
        if not isinstance(flowable, Paragraph):
            return
        if flowable.style.name not in {"H1Report", "H2Report"}:
            return
        level = 0 if flowable.style.name == "H1Report" else 1
        text = flowable.getPlainText()
        key = f"section-{self.seq.nextf('toc')}"
        self.canv.bookmarkPage(key)
        self.canv.addOutlineEntry(text, key, level=level, closed=False)
        self.notify("TOCEntry", (level, text, self.page, key))


def P(text: str, style: str = "BodyReport") -> Paragraph:
    return Paragraph(text, styles[style])


def H1(text: str) -> Paragraph:
    return P(text, "H1Report")


def H2(text: str) -> Paragraph:
    return P(text, "H2Report")


def H3(text: str) -> Paragraph:
    return P(text, "H3Report")


def B(text: str) -> Paragraph:
    return P("• " + text, "BulletReport")


def code_block(text: str) -> Preformatted:
    return Preformatted(text.strip("\n"), styles["CodeReport"], maxLineLength=95)


def caption(text: str) -> Paragraph:
    return P(text, "Caption")


def table(data, widths, header=True, font_size=7.7):
    converted = []
    for row in data:
        converted.append([
            value if isinstance(value, Flowable) else P(str(value), "Small")
            for value in row
        ])
    t = Table(converted, colWidths=widths, repeatRows=1 if header else 0, hAlign="LEFT")
    ts = [
        ("VALIGN", (0, 0), (-1, -1), "TOP"),
        ("GRID", (0, 0), (-1, -1), 0.45, LIGHT_LINE),
        ("LEFTPADDING", (0, 0), (-1, -1), 5),
        ("RIGHTPADDING", (0, 0), (-1, -1), 5),
        ("TOPPADDING", (0, 0), (-1, -1), 4),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
        ("FONTNAME", (0, 0), (-1, -1), "Arial"),
        ("FONTSIZE", (0, 0), (-1, -1), font_size),
    ]
    if header:
        ts += [
            ("BACKGROUND", (0, 0), (-1, 0), NAVY),
            ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
            ("FONTNAME", (0, 0), (-1, 0), "Arial-Bold"),
        ]
    for i in range(1 if header else 0, len(data)):
        if i % 2 == 0:
            ts.append(("BACKGROUND", (0, i), (-1, i), colors.HexColor("#F7FAFB")))
    t.setStyle(TableStyle(ts))
    return t


def label_value_table(rows):
    data = [[P(f"<b>{label}</b>", "Small"), P(value, "Small")] for label, value in rows]
    t = Table(data, colWidths=[42 * mm, 112 * mm], hAlign="CENTER")
    t.setStyle(
        TableStyle(
            [
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LINEBELOW", (0, 0), (-1, -1), 0.35, LIGHT_LINE),
                ("LEFTPADDING", (0, 0), (-1, -1), 5),
                ("RIGHTPADDING", (0, 0), (-1, -1), 5),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
            ]
        )
    )
    return t


def arrow(d: Drawing, x1, y1, x2, y2, color=TEAL):
    d.add(Line(x1, y1, x2, y2, strokeColor=color, strokeWidth=1.3))
    d.add(Polygon(points=[x2, y2, x2 - 6, y2 + 3.5, x2 - 6, y2 - 3.5], fillColor=color, strokeColor=color))


def system_flow_diagram() -> Drawing:
    d = Drawing(470, 145)
    boxes = [
        (5, 82, 78, 42, "Doctor UI", "selects medicines"),
        (101, 82, 95, 42, "Controller", "validates role/input"),
        (214, 82, 115, 42, "Creation Service", "coordinates workflow"),
        (351, 82, 112, 42, "Pharmacy REST", "XML request/response"),
        (214, 12, 115, 42, "Eloquent ORM", "transactional save"),
        (351, 12, 112, 42, "Patient Record DB", "own schema only"),
    ]
    for x, y, w, h, title, subtitle in boxes:
        d.add(Rect(x, y, w, h, rx=5, ry=5, fillColor=PALE_BLUE, strokeColor=LIGHT_LINE))
        d.add(String(x + w / 2, y + 25, title, fontName="Arial-Bold", fontSize=8.2, fillColor=NAVY, textAnchor="middle"))
        d.add(String(x + w / 2, y + 11, subtitle, fontName="Arial", fontSize=6.6, fillColor=MUTED, textAnchor="middle"))
    arrow(d, 83, 103, 101, 103)
    arrow(d, 196, 103, 214, 103)
    arrow(d, 329, 103, 351, 103)
    arrow(d, 271, 82, 271, 54)
    arrow(d, 329, 33, 351, 33)
    d.add(String(362, 64, "S / F / E", fontName="Arial-Bold", fontSize=7, fillColor=SUCCESS))
    return d


def entity_diagram() -> Drawing:
    d = Drawing(470, 265)

    def cls(x, y, w, h, title, attrs):
        d.add(Rect(x, y, w, h, fillColor=colors.white, strokeColor=NAVY, strokeWidth=1))
        d.add(Rect(x, y + h - 25, w, 25, fillColor=PALE_BLUE, strokeColor=NAVY, strokeWidth=1))
        d.add(String(x + w / 2, y + h - 16, title, fontName="Arial-Bold", fontSize=8.4, fillColor=NAVY, textAnchor="middle"))
        yy = y + h - 39
        for a in attrs:
            d.add(String(x + 7, yy, a, fontName="Consolas", fontSize=6.5, fillColor=INK))
            yy -= 12

    cls(7, 150, 120, 92, "PatientEntity", ["id: string", "name: string", "records(): HasMany"])
    cls(171, 130, 150, 132, "PatientRecordEntity", ["id: string", "patient: PatientEntity", "condition: ConditionEntity", "doctorId: string", "severity: string", "remark: encrypted string", "recordDate: date", "prescriptions(): HasMany"])
    cls(360, 150, 104, 92, "ConditionEntity", ["id: string", "name: string", "description: string", "records(): HasMany"])
    cls(7, 15, 170, 105, "PatientRecordAccessLogEntity", ["patientRecord: PatientRecordEntity", "accessorId: string", "accessorRole: string", "accessType: string", "accessedAt: DateTime"])
    cls(285, 15, 179, 105, "PatientRecordPrescriptionEntity", ["patientRecord: PatientRecordEntity", "prescriptionReference: string", "createdAt: DateTime"])
    arrow(d, 127, 196, 171, 196, NAVY)
    arrow(d, 360, 196, 321, 196, NAVY)
    arrow(d, 210, 130, 160, 120, NAVY)
    arrow(d, 280, 130, 330, 120, NAVY)
    d.add(String(142, 204, "1      0..*", fontName="Arial", fontSize=6.7, fillColor=MUTED))
    d.add(String(326, 204, "0..*      1", fontName="Arial", fontSize=6.7, fillColor=MUTED))
    d.add(String(158, 126, "1      0..*", fontName="Arial", fontSize=6.7, fillColor=MUTED))
    d.add(String(306, 126, "0..*      1", fontName="Arial", fontSize=6.7, fillColor=MUTED))
    return d


def cropped_detail_image() -> Image | None:
    if not DETAIL_SCREENSHOT.exists():
        return None
    image = PILImage.open(DETAIL_SCREENSHOT).convert("RGB")
    crop = image.crop((20, 0, min(image.width, 900), min(image.height, 495)))
    buffer = BytesIO()
    crop.save(buffer, format="PNG")
    buffer.seek(0)
    flow = Image(buffer, width=158 * mm, height=89 * mm)
    flow._report_buffer = buffer
    return flow


def record_detail_figure() -> Drawing:
    """Vector reconstruction of the implemented final show.php layout."""
    d = Drawing(470, 262)
    d.add(String(8, 240, "Patient Record Details", fontName="Arial-Bold", fontSize=17, fillColor=NAVY))
    d.add(String(8, 225, "Record PR010", fontName="Arial", fontSize=8, fillColor=MUTED))
    d.add(Rect(326, 226, 137, 22, rx=11, ry=11, fillColor=colors.HexColor("#E7F3F0"), strokeColor=None))
    d.add(String(394.5, 234, "CONFIDENTIAL MEDICAL INFORMATION", fontName="Arial-Bold", fontSize=5.7, fillColor=SUCCESS, textAnchor="middle"))
    d.add(Rect(8, 22, 455, 190, rx=8, ry=8, fillColor=colors.white, strokeColor=LIGHT_LINE, strokeWidth=0.8))
    d.add(String(25, 191, "Patient information", fontName="Arial-Bold", fontSize=10, fillColor=NAVY))
    info = [
        (25, 173, "PATIENT NAME", "Aisyah Rahman"),
        (247, 173, "PATIENT ID", "PA002"),
        (25, 140, "RECORD DATE", "2026-09-04"),
        (247, 140, "SEVERITY", "Moderate"),
    ]
    for x, y, label, value in info:
        d.add(String(x, y, label, fontName="Arial-Bold", fontSize=6, fillColor=MUTED))
        d.add(String(x, y - 13, value, fontName="Arial", fontSize=8.5, fillColor=INK))
    d.add(Line(8, 118, 463, 118, strokeColor=LIGHT_LINE, strokeWidth=0.6))
    d.add(String(25, 101, "Clinical information", fontName="Arial-Bold", fontSize=10, fillColor=NAVY))
    clinical = [
        (25, 83, "CONDITION", "Asthma"),
        (180, 83, "DOCTOR ID", "DC001"),
        (320, 83, "PRESCRIPTION REFERENCE", "RX-3A8D6F91"),
    ]
    for x, y, label, value in clinical:
        d.add(String(x, y, label, fontName="Arial-Bold", fontSize=5.7, fillColor=MUTED))
        d.add(String(x, y - 13, value, fontName="Arial", fontSize=8.2, fillColor=INK))
    d.add(Rect(25, 35, 413, 25, rx=4, ry=4, fillColor=colors.HexColor("#F6F9FA"), strokeColor=None))
    d.add(String(33, 45, "Remark: Asthma symptoms reviewed; follow prescription instructions.", fontName="Arial", fontSize=7.2, fillColor=INK))
    d.add(String(25, 10, "Accessed by: Doctor DC001     Access time: 2026-09-04 14:20:31", fontName="Arial", fontSize=6.8, fillColor=MUTED))
    return d


def design_pattern_image() -> Image | None:
    if not DESIGN_PATTERN_IMAGE.exists():
        return None
    return Image(str(DESIGN_PATTERN_IMAGE), width=165 * mm, height=126 * mm)


def build_story():
    story = []

    # Cover page
    story += [Spacer(1, 21 * mm)]
    story.append(P("BMIT3173 INTEGRATIVE PROGRAMMING", "ReportSubtitle"))
    story.append(HRFlowable(width="38%", thickness=2, color=TEAL, spaceBefore=4, spaceAfter=17))
    story.append(P("ASSIGNMENT 202605", "ReportTitle"))
    story.append(P("MediCare Connect", "ReportSubtitle"))
    story.append(Spacer(1, 7 * mm))
    story.append(P("PATIENT RECORD MODULE", "ReportTitle"))
    story.append(Spacer(1, 18 * mm))
    story.append(
        label_value_table(
            [
                ("Student Name", "Heng Chin Yang"),
                ("Student ID", "25WMR09726"),
                ("Programme", "Bachelor of Information Technology (Honours) in Information Security"),
                ("Tutorial Group", "RIS3G5"),
                ("System Title", "MediCare Connect"),
                ("Chosen SDG", "SDG 3: Good Health and Well-Being"),
                ("Individual Module", "Patient Record"),
                ("Submission Date", "6 September 2026"),
            ]
        )
    )
    story.append(Spacer(1, 14 * mm))
    story.append(P("Individual Technical Report", "ReportSubtitle"))
    story.append(PageBreak())

    # Declaration / AI disclosure
    story.append(H1("Student Declaration and AI Usage Disclosure"))
    story.append(H2("Plagiarism statement"))
    story.append(P(
        "I understand that plagiarism is presenting another person's work, ideas, code, or wording as my own. "
        "I will ensure that the submitted report accurately describes the implementation I can explain and demonstrate, "
        "and that all external sources and permitted AI assistance are acknowledged."
    ))
    story.append(Spacer(1, 5 * mm))
    story.append(label_value_table([("Student signature", "____________________________"), ("Date", "____________________________")]))
    story.append(Spacer(1, 8 * mm))
    story.append(H2("AI usage disclosure"))
    story.append(P(
        "Generative AI assistance was used during development and report preparation. The assistance included concept "
        "explanations, code review, debugging support, test planning, and drafting/formatting this report. I reviewed the "
        "suggestions, adapted them to the Patient Record module, executed the automated tests, and remain responsible for "
        "the accuracy of the submitted work and for explaining it during the demonstration."
    ))
    story.append(
        table(
            [
                ["Tool", "Purpose", "How the output was handled"],
                ["OpenAI ChatGPT / Codex", "Explanations, code review, debugging, integration-test planning, and report drafting.", "Suggestions were reviewed against the actual PHP project and verified through local automated tests."],
            ],
            [34 * mm, 58 * mm, 68 * mm],
        )
    )
    story.append(P(
        "<b>Student action before submission:</b> confirm the student ID, sign and date this page, and adjust the disclosure "
        "if any additional AI tools were used.", "Callout"
    ))
    story.append(PageBreak())

    # Table of contents
    story.append(H1("Table of Contents"))
    toc = TableOfContents()
    toc.levelStyles = [
        ParagraphStyle(name="TOC1", fontName="Arial-Bold", fontSize=10, leading=15, leftIndent=0, firstLineIndent=0, textColor=NAVY, spaceBefore=4),
        ParagraphStyle(name="TOC2", fontName="Arial", fontSize=8.7, leading=12, leftIndent=14, firstLineIndent=0, textColor=INK),
    ]
    story.append(toc)
    story.append(Spacer(1, 8 * mm))
    story.append(P(
        "The report follows the section order required by the supplied assignment report template and addresses the "
        "highest-band marking criteria for functionality, MVC/ORM, design pattern, secure coding, web services, and independent learning.",
        "Callout",
    ))
    story.append(PageBreak())

    # 1 Introduction
    story.append(H1("1. Introduction to the System"))
    story.append(H2("1.1 System overview"))
    story.append(P(
        "MediCare Connect is a modular healthcare information system developed by a team of four students. Its four "
        "modules are User Management, Appointment, Pharmacy, and Patient Record. Each module is deployed as a separate "
        "PHP folder under the XAMPP htdocs directory and owns its own database. Cross-module information is exchanged "
        "through REST endpoints carrying XML messages, instead of reading another module's database directly."
    ))
    story.append(P(
        "My individual responsibility is the Patient Record module. It stores a patient's clinical record history, links "
        "each record to a condition and responsible doctor, records access activity, and stores only an external "
        "prescription reference for Pharmacy-owned prescription details. A patient may view only their own records. A "
        "doctor may view and maintain records only for patients assigned to that doctor, while an administrator has "
        "authorised maintenance access."
    ))
    story.append(H2("1.2 Sustainable Development Goal"))
    story.append(P(
        "The chosen goal is <b>United Nations SDG 3: Good Health and Well-Being</b>. The system supports continuity of care "
        "by making authorised clinical history available to the right user, reducing fragmented information, and linking "
        "medical-record creation with Pharmacy stock validation. These functions do not replace clinical judgement; they "
        "support safer information handling and more coordinated service delivery."
    ))
    story.append(H2("1.3 Scope and target users"))
    story.append(B("Patients review their own medical history and linked prescription outcome."))
    story.append(B("Doctors select an assigned patient, create or update records, select one or more medicines, and receive immediate stock/integration feedback."))
    story.append(B("Administrators and doctors maintain the reusable condition catalogue according to role permissions."))
    story.append(B("Other modules consume the Patient Record XML service using the agreed request/response fields."))
    story.append(H2("1.4 Technology and architecture"))
    story.append(
        table(
            [
                ["Layer / concern", "Implementation"],
                ["Language and runtime", "PHP 8.2 on XAMPP/Apache with MySQL"],
                ["MVC", "index.php routes to controllers; controllers coordinate models/services and load PHP views"],
                ["ORM", "Illuminate Database / Eloquent 12, used as standalone Composer packages"],
                ["Design pattern", "Decorator for confidentiality, access context, and severe-condition alert behaviour"],
                ["Integration", "REST over HTTP with XML payloads, XSD validation, requestID, timestamp, status, and API key"],
                ["Security", "Bound ORM queries, AES-256-GCM encryption, access control, CSRF, hardened sessions, and output escaping"],
            ],
            [46 * mm, 114 * mm],
        )
    )
    story.append(PageBreak())

    # 2 Module description
    story.append(H1("2. Module Description"))
    story.append(H2("2.1 Main functions"))
    story.append(
        table(
            [
                ["Function", "User behaviour and rule", "Main implementation path"],
                ["Authentication boundary", "Uses the shared role, username, user_id, and patient_id session contract. Unauthenticated requests receive HTTP 401.", "Shared/SessionSecurity.php; index.php"],
                ["Patient history", "A patient sees only records whose patient_id matches the authenticated patient_id.", "PatientRecordController::index(); View/patient_record/index.php"],
                ["Doctor patient list", "A doctor sees existing responsible patients and appointment assignments returned by REST, solving the first-record problem.", "doctorPatients(); AppointmentServiceClient.php"],
                ["Create record", "The doctor ID is taken from the session. Patient and doctor identifiers are not typed into the form. Condition, severity, date, remark, and medicines are validated.", "PatientRecordController::store()"],
                ["Pharmacy workflow", "The user selects one or many catalogue medicines. Stock is checked again on the server before an approved dispensing request is created.", "PatientRecordCreationService.php; PharmacyServiceClient.php"],
                ["View record", "Displays the record, decorator effects, access time, severe-condition alert, and linked Pharmacy prescription details.", "PatientRecordController::show(); show.php"],
                ["Audit trail", "VIEW, CREATE, and UPDATE events are saved with accessor ID, name, role, type, and time.", "PatientRecordAccessLog.php"],
                ["Condition maintenance", "Doctors/admins add and modify the medical-condition catalogue; patients are denied.", "ConditionController.php"],
            ],
            [34 * mm, 78 * mm, 49 * mm],
            font_size=7.2,
        )
    )
    story.append(H2("2.2 MVC request lifecycle"))
    story.append(P(
        "The front controller starts the secure session, rejects unauthenticated users, loads the ORM and controllers, "
        "checks CSRF for every modifying POST, and dispatches the requested action. The controller performs role and "
        "ownership checks, calls the model or integration service, and selects the view. Views do not build SQL queries."
    ))
    story.append(code_block(
        "// index.php (simplified)\n"
        "SessionSecurity::start();\n"
        "if (!SessionSecurity::isAuthenticated()) { /* HTTP 401 */ }\n"
        "require 'Shared/orm.php';\n"
        "$controller = new PatientRecordController();\n"
        "switch ($action) {\n"
        "  case 'index':  $controller->index(); break;\n"
        "  case 'store':  $controller->store(); break;\n"
        "  case 'show':   $controller->show($id); break;\n"
        "}"
    ))
    story.append(caption("Code Extract 1. Front-controller routing and MVC separation (index.php)."))
    story.append(H2("2.3 Creating a record with medicines"))
    story.append(system_flow_diagram())
    story.append(caption("Figure 1. Patient Record creation flow and REST boundary."))
    story.append(P(
        "The form catalogue improves usability, but it is not trusted as a security boundary. The server validates every "
        "SKU and quantity and then calls Pharmacy for real-time availability. If any item is unavailable, the local record "
        "is not saved. If Pharmacy succeeds but the local database fails, the generated prescription reference is logged "
        "for reconciliation and the user receives a controlled service error."
    ))
    story.append(H2("2.4 Interface evidence"))
    story.append(record_detail_figure())
    story.append(caption("Figure 2. Final Patient Record detail-view layout implemented by View/patient_record/show.php."))
    story.append(P(
        "The final interface uses the Pharmacy module's shared stylesheet and favicon to give the team system a consistent "
        "appearance. Decorator effects are integrated into the detail card as a confidentiality label, access information, "
        "and a conditional emergency alert rather than being displayed as duplicate debug output."
    ))
    story.append(H2("2.5 Robustness and maintainability"))
    story.append(B("Pagination limits record-history pages to ten records and clamps invalid page numbers."))
    story.append(B("Transactions protect local record and prescription-reference creation from partial local writes."))
    story.append(B("HTTP errors use suitable status codes, including 401, 403, 404, 405, 419, 422, 502, and 503."))
    story.append(B("External-service timeouts are five seconds and failures are converted to safe user-facing messages."))
    story.append(B("Module-specific database accounts enforce that Patient Record cannot query Pharmacy or User Management tables."))
    story.append(H2("2.6 Automated verification result"))
    story.append(P(
        "On 4 September 2026, the three command-line test suites were executed with C:\\xampp\\php\\php.exe. All "
        "application-security, Pharmacy integration, and Patient Record XML service checks passed. The detailed cases "
        "are reported in Section 6.8."
    ))
    story.append(PageBreak())

    # 3 Entity Classes
    story.append(H1("3. Entity Classes and ORM"))
    story.append(H2("3.1 Entity class diagram"))
    story.append(entity_diagram())
    story.append(caption("Figure 3. Patient Record Eloquent entity classes and object relationships."))
    story.append(P(
        "The diagram is a class-oriented view, not an entity-relationship diagram. Relationships are represented in PHP "
        "as object-returning Eloquent methods such as patient(), condition(), prescriptions(), and patientRecord(). The "
        "database still uses foreign-key columns internally, but controllers work through entity objects and relations."
    ))
    story.append(H2("3.2 Entity responsibilities"))
    story.append(
        table(
            [
                ["Entity class", "Table", "Responsibility and relations"],
                ["PatientEntity", "patients", "Patient identity cached for this module; has many PatientRecordEntity objects."],
                ["ConditionEntity", "conditions", "Reusable condition name/description; has many PatientRecordEntity objects."],
                ["PatientRecordEntity", "patient_records", "Clinical record aggregate; belongs to PatientEntity and ConditionEntity; has prescriptions."],
                ["PatientRecordPrescriptionEntity", "patient_record_prescriptions", "Stores only the external Pharmacy prescription reference; belongs to PatientRecordEntity."],
                ["PatientRecordAccessLogEntity", "patient_record_access_logs", "Persistent VIEW/CREATE/UPDATE audit events; belongs to PatientRecordEntity."],
            ],
            [44 * mm, 40 * mm, 76 * mm],
        )
    )
    story.append(H2("3.3 Eloquent ORM implementation"))
    story.append(P(
        "Eloquent is installed through Composer as illuminate/database version 12. Models use string primary keys because "
        "the agreed identifiers are PA001, DC001, and PR001 formats. Eager loading prevents repeated patient/condition "
        "lookups while the ORM's query builder binds values instead of concatenating them into SQL."
    ))
    story.append(code_block(
        "// Model/PatientRecord.php\n"
        "$records = PatientRecordEntity::query()\n"
        "    ->with(['patient', 'condition', 'prescriptions'])\n"
        "    ->where('patient_id', $patientId)\n"
        "    ->where('doctor_id', $doctorId)\n"
        "    ->orderByDesc('record_date')\n"
        "    ->get();"
    ))
    story.append(caption("Code Extract 2. Eloquent relationships and parameter-bound query construction."))
    story.append(H2("3.4 Encryption through an ORM attribute cast"))
    story.append(P(
        "PatientRecordEntity defines a custom Eloquent Attribute for remark. The set transformation encrypts before the "
        "value reaches MySQL; the get transformation decrypts only after an authorised query. Therefore controllers and "
        "views work with normal text while the database stores authenticated ciphertext."
    ))
    story.append(code_block(
        "protected function remark(): Attribute\n"
        "{\n"
        "    return Attribute::make(\n"
        "        get: fn (?string $v) => SensitiveDataCipher::instance()->decrypt($v ?? ''),\n"
        "        set: fn (?string $v) => SensitiveDataCipher::instance()->encrypt($v ?? '')\n"
        "    );\n"
        "}"
    ))
    story.append(caption("Code Extract 3. Transparent encryption/decryption at the ORM boundary."))
    story.append(PageBreak())

    # 4 Design Pattern
    story.append(H1("4. Design Pattern: Decorator"))
    story.append(H2("4.1 Description of the pattern"))
    story.append(P(
        "Decorator is a structural design pattern that adds responsibilities to an object by wrapping it in another object "
        "with the same interface. Each wrapper delegates to the wrapped component and adds behaviour before or after the "
        "delegation. The wrappers can be stacked in different combinations without creating subclasses for every possible "
        "combination of confidentiality, auditing, and emergency warning."
    ))
    story.append(H2("4.2 Implementation class diagram"))
    dp = design_pattern_image()
    if dp is not None:
        story.append(dp)
        story.append(caption("Figure 4. Decorator design-pattern class diagram used by the Patient Record module."))
    story.append(H2("4.3 Mapping the diagram to PHP classes"))
    story.append(
        table(
            [
                ["Pattern role", "Project class", "Behaviour"],
                ["Component", "PatientRecordInterface", "Defines getDetails(): string for every base or decorated record."],
                ["Concrete component", "BasicPatientRecord", "Builds the normal record ID, patient, condition, and remark representation."],
                ["Base decorator", "PatientRecordDecorator", "Stores a protected PatientRecordInterface reference and delegates getDetails()."],
                ["Concrete decorator", "ConfidentialityDecorator", "Adds a security classification label."],
                ["Concrete decorator", "AuditTrailDecorator", "Adds the accessor and access time to the representation."],
                ["Concrete decorator", "EmergencyAlertDecorator", "Adds a warning only when severity is Severe."],
            ],
            [34 * mm, 49 * mm, 77 * mm],
        )
    )
    story.append(PageBreak())

    story.append(H2("4.4 Runtime implementation"))
    story.append(P(
        "The controller starts with BasicPatientRecord, then wraps it with confidentiality and audit decorators. The "
        "EmergencyAlertDecorator is added conditionally. Since every wrapper implements the same interface, the view "
        "receives one final PatientRecordInterface representation regardless of the number of decorators. Separately, the "
        "audit event is persisted in patient_record_access_logs so that the user-visible decoration is supported by durable "
        "evidence rather than being only display text."
    ))
    story.append(code_block(
        "$patientRecord = new BasicPatientRecord($id, $name, $condition, $remark);\n"
        "$patientRecord = new ConfidentialityDecorator($patientRecord, $securityLabel);\n"
        "$patientRecord = new AuditTrailDecorator($patientRecord, $accessedBy, $accessTime);\n"
        "if ($severity === 'Severe') {\n"
        "    $patientRecord = new EmergencyAlertDecorator($patientRecord, $message);\n"
        "}\n"
        "$decoratedDetails = $patientRecord->getDetails();"
    ))
    story.append(caption("Code Extract 4. Decorators composed at runtime in PatientRecordController::show()."))
    story.append(H2("4.5 Why Decorator is suitable"))
    story.append(B("It solves a real cross-cutting presentation problem: a record may require confidentiality, auditing context, and an emergency alert at the same time."))
    story.append(B("Open/closed principle: a future MaskedPatientNameDecorator or PrintingWatermarkDecorator can be added without modifying BasicPatientRecord."))
    story.append(B("Single responsibility: each concrete decorator has one clear addition, making testing and explanation easier."))
    story.append(B("Runtime flexibility: the severe alert is included only for Severe records while the other wrappers remain unchanged."))
    story.append(P(
        "Inheritance alone would require many combined subclasses, for example ConfidentialAuditRecord and "
        "ConfidentialAuditEmergencyRecord. Decorator avoids this class explosion. A simple helper function would also work "
        "for formatting, but it would not demonstrate substitutable objects or allow independent composition and testing."
    ))
    story.append(H2("4.6 Limitation and mitigation"))
    story.append(P(
        "Decorator order affects the exact output order, and many small wrapper objects can make debugging harder. The "
        "module mitigates this by composing the decorators in one controller method, using descriptive class names, and "
        "testing all three decorators independently. ConfidentialityDecorator is not treated as encryption or access "
        "control; those security controls are implemented separately."
    ))
    story.append(PageBreak())

    # 5 Security
    story.append(H1("5. Software Security"))
    story.append(H2("5.1 Threat 1: SQL injection"))
    story.append(P(
        "SQL injection occurs when untrusted input becomes part of the SQL command structure. For example, if patient_id "
        "were concatenated into a WHERE clause, an attacker could add SQL syntax to bypass ownership filtering, disclose "
        "another patient's records, change data, or damage tables. Healthcare records make the confidentiality and "
        "integrity impact especially serious."
    ))
    story.append(H3("Secure coding practice: strongly typed parameterised ORM queries"))
    story.append(P(
        "All application database operations use Eloquent's query builder or entity save/create methods. Values passed to "
        "where(), find(), create(), and update() are sent through PDO bindings instead of string concatenation. In addition, "
        "identifiers are allow-listed using formats such as ^PA[0-9]{3,8}$, quantities are validated as integers, severity "
        "uses a fixed list, and record dates must round-trip through DateTimeImmutable. Parameterisation is the main SQL "
        "injection control; validation provides defence in depth."
    ))
    story.append(code_block(
        "public function findById(string $recordId): ?array\n"
        "{\n"
        "    $record = PatientRecordEntity::query()\n"
        "        ->with(['patient', 'condition', 'prescriptions'])\n"
        "        ->find($recordId); // bound string primary-key value\n"
        "    return $record === null ? null : $this->toViewArray($record);\n"
        "}"
    ))
    story.append(caption("Code Extract 5. No user-controlled value is concatenated into SQL."))
    story.append(H3("How the control can be demonstrated"))
    story.append(B("Request a record with an invalid identifier such as PR001' OR '1'='1; the ID format check returns not found and no SQL syntax is executed."))
    story.append(B("Use a valid ID and show that only that bound value is queried."))
    story.append(B("Show Model/PatientRecord.php and the Eloquent entity methods to the examiner."))
    story.append(B("Explain that escaping output prevents XSS, but it is not the SQL injection control."))
    story.append(PageBreak())

    story.append(H2("5.2 Threat 2: data breach through unencrypted stored data"))
    story.append(P(
        "A database backup, copied data directory, compromised database account, or administrator mistake can expose "
        "stored medical remarks even if the web screens are protected. Plaintext clinical notes reveal sensitive health "
        "information immediately. Access control alone does not protect against an attacker who obtains only the database."
    ))
    story.append(H3("Secure coding practice: AES-256-GCM authenticated encryption"))
    story.append(P(
        "SensitiveDataCipher encrypts clinical remarks with AES-256-GCM. A fresh 12-byte random IV is generated for each "
        "value, and the 16-byte authentication tag detects modification. The stored value begins with enc:v1: so encrypted "
        "rows can be identified and a later key-version migration is possible. Additional authenticated data binds the "
        "ciphertext to the Patient Record remark purpose."
    ))
    story.append(code_block(
        "$iv = random_bytes(12);\n"
        "$ciphertext = openssl_encrypt(\n"
        "    $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA,\n"
        "    $iv, $tag, 'medicare-connect:patient-record-remark:v1', 16\n"
        ");\n"
        "return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);"
    ))
    story.append(caption("Code Extract 6. Authenticated encryption for sensitive clinical remarks."))
    story.append(P(
        "The 32-byte encryption key is read from a file outside htdocs and is not stored in the database or source "
        "repository. If the key is missing, malformed, or the ciphertext authentication fails, the operation fails closed. "
        "This supports key/data separation recommended for protecting stored data."
    ))
    story.append(H3("How the control can be demonstrated"))
    story.append(B("Create a record containing a recognisable phrase, then query patient_records.remark in phpMyAdmin; only an enc:v1: Base64 payload should be visible."))
    story.append(B("Open the same record through the authorised application; the ORM accessor decrypts it for display."))
    story.append(B("Run tests/application_security_test.php to show encryption, correct decryption, and tamper rejection all pass."))
    story.append(H2("5.3 Additional defence-in-depth controls"))
    story.append(
        table(
            [
                ["Control", "Implementation and purpose"],
                ["Role and ownership checks", "Patients only access their patient_id; doctors require responsibility; admins have explicit privileged access."],
                ["CSRF tokens", "Every modifying browser POST must contain a 256-bit random token verified with hash_equals()."],
                ["Hardened sessions", "Strict mode, cookie-only sessions, HttpOnly, SameSite=Lax, Secure on HTTPS, and session-ID regeneration."],
                ["Output encoding", "htmlspecialchars() is used by views to stop stored or reflected HTML/script execution."],
                ["API authentication", "REST services require X-API-Key values loaded from files outside the web root."],
                ["XML hardening", "DTD/entity declarations are rejected; LIBXML_NONET prevents network entity resolution; XSD validates structure."],
                ["Least-privilege database", "patient_record_app has access only to medicare_connect2; other modules have separate credentials."],
                ["Audit logging", "VIEW, CREATE, and UPDATE events are written to a related log table with identity, role, action, and time."],
            ],
            [43 * mm, 117 * mm],
        )
    )
    story.append(PageBreak())

    # 6 Web services
    story.append(H1("6. Web Services"))
    story.append(H2("6.1 Technology choice"))
    story.append(P(
        "The module uses REST-style HTTP endpoints with XML payloads. REST was selected because the teammates' PHP "
        "modules already expose HTTP routes and do not require SOAP envelopes or WSDL tooling. XML remains the agreed data "
        "format, and XSD supplies strict machine-verifiable message contracts. Each module owns its database, so REST is "
        "the only supported way to obtain cross-module information."
    ))
    story.append(H2("6.2 Service exposure: Patient Record summary"))
    story.append(
        table(
            [
                ["IFA item", "Value"],
                ["Protocol", "REST / HTTP POST with application/xml"],
                ["Function", "Retrieve authorised Patient Record summary data by patient ID"],
                ["Source module", "Patient Record"],
                ["Target module", "Appointment, Pharmacy, or another approved MediCare Connect module"],
                ["URL", "http://localhost/project/api/patient-record-summary.php"],
                ["Authentication", "X-API-Key request header"],
                ["Implementation", "api/patient-record-summary.php; WebService/PatientRecordXmlService.php"],
            ],
            [45 * mm, 115 * mm],
        )
    )
    story.append(H3("Request parameters"))
    story.append(
        table(
            [
                ["Field", "Type", "M/O", "Description", "Format"],
                ["requestID", "String", "M", "Unique correlation ID generated by the caller", "1-64 letters, digits, _ or -"],
                ["timeStamp", "String", "M", "Time the request was generated", "YYYY-MM-DD HH:MM:SS; within 5 minutes"],
                ["patientID", "String", "M", "Patient whose summary is required", "PA followed by 3-8 digits"],
            ],
            [27 * mm, 22 * mm, 14 * mm, 58 * mm, 39 * mm],
            font_size=7.1,
        )
    )
    story.append(code_block(
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        "<patientRecordRequest>\n"
        "  <requestID>REQ-DEMO-001</requestID>\n"
        "  <timeStamp>2026-09-04 14:00:00</timeStamp>\n"
        "  <patientID>PA002</patientID>\n"
        "</patientRecordRequest>"
    ))
    story.append(caption("XML Example 1. Patient Record service request."))
    story.append(PageBreak())

    story.append(H3("Response parameters"))
    story.append(
        table(
            [
                ["Field", "Type", "M/O", "Description"],
                ["requestID", "String", "M", "Echoes the request correlation ID."],
                ["status", "String", "M", "S = success, F = business failure/not found, E = malformed/technical/authentication error."],
                ["timeStamp", "String", "M", "Server response time in YYYY-MM-DD HH:MM:SS."],
                ["message", "String", "M", "Human-readable result without internal exception details."],
                ["patient", "Object", "O", "patientID and patientName when data is found."],
                ["records", "List", "O", "recordID, doctorID, condition, severity, and recordDate. Sensitive remark is intentionally excluded."],
            ],
            [26 * mm, 22 * mm, 14 * mm, 98 * mm],
        )
    )
    story.append(code_block(
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
        "<patientRecordResponse>\n"
        "  <requestID>REQ-DEMO-001</requestID><status>S</status>\n"
        "  <timeStamp>2026-09-04 14:00:01</timeStamp>\n"
        "  <message>Patient Record summary retrieved successfully.</message>\n"
        "  <patient><patientID>PA002</patientID><patientName>Aisyah Rahman</patientName></patient>\n"
        "  <records><record><recordID>PR010</recordID><doctorID>DC001</doctorID>\n"
        "    <condition>Asthma</condition><severity>Moderate</severity>\n"
        "    <recordDate>2026-09-04</recordDate></record></records>\n"
        "</patientRecordResponse>"
    ))
    story.append(caption("XML Example 2. Successful Patient Record summary response."))
    story.append(H3("Exposure safeguards"))
    story.append(B("POST is mandatory; other methods receive 405 and an XML error response."))
    story.append(B("The API key is compared securely and failed authentication receives 401/E."))
    story.append(B("Request size is limited to 65,536 bytes and stale timestamps are rejected."))
    story.append(B("Request and response must validate against patient_record_request.xsd and patient_record_response.xsd."))
    story.append(B("Clinical remarks are excluded from the exposed summary to minimise sensitive data disclosure."))
    story.append(H2("6.3 Service consumption: Pharmacy"))
    story.append(
        table(
            [
                ["IFA item", "Value"],
                ["Protocol", "REST / HTTP POST with namespaced XML"],
                ["Source module", "Patient Record"],
                ["Target module", "Pharmacy"],
                ["URL", "http://localhost/MediCareConnect-Pharmacy-Lawliet/MediCareConnect-Pharmacy-Lawliet/api/pharmacy"],
                ["Operations", "getMedicineCatalog; getMedicineAvailability; createApprovedDispensingRequest; getPrescription"],
                ["Authentication", "X-API-Key loaded from medicare-connect-secrets/pharmacy-service.key"],
                ["Implementation", "Service/PharmacyServiceClient.php and Service/xsd/pharmacy_*.xsd"],
            ],
            [45 * mm, 115 * mm],
        )
    )
    story.append(PageBreak())

    story.append(H2("6.4 Pharmacy availability request and response"))
    story.append(P(
        "Before a Patient Record is saved, each selected medicine is checked with the requested quantity. The response must "
        "have HTTP 200, status S, the same requestID, the same SKU and quantity, and available equal to true. The local form's "
        "maximum is a usability feature; the server-side Pharmacy response is the authoritative stock decision."
    ))
    story.append(code_block(
        "<m:getMedicineAvailability xmlns:m=\"urn:medicare:patient-record\">\n"
        "  <m:requestID>REQ-A1B2C3D4</m:requestID>\n"
        "  <m:timestamp>2026-09-04 14:10:00</m:timestamp>\n"
        "  <m:sku>MED-SALB-100</m:sku><m:quantity>2</m:quantity>\n"
        "</m:getMedicineAvailability>\n\n"
        "<m:getMedicineAvailabilityResponse xmlns:m=\"urn:medicare:patient-record\">\n"
        "  <m:status>S</m:status><m:timestamp>2026-09-04 14:10:01</m:timestamp>\n"
        "  <m:message>Availability checked.</m:message><m:requestID>REQ-A1B2C3D4</m:requestID>\n"
        "  <m:sku>MED-SALB-100</m:sku><m:requestedQuantity>2</m:requestedQuantity>\n"
        "  <m:available>true</m:available><m:availableQuantity>7</m:availableQuantity>\n"
        "</m:getMedicineAvailabilityResponse>"
    ))
    story.append(caption("XML Example 3. Correlated medicine-availability request and response."))
    story.append(H2("6.5 Multiple-medicine approved dispensing request"))
    story.append(P(
        "The request contains a generated prescriptionReference, patient and prescriber external identifiers, and one or "
        "more item elements. The Patient Record service accepts success only when Pharmacy returns status S, the matching "
        "requestID/reference, and dispensingStatus approved. Pharmacy owns the medicine and dispensing data; the local "
        "database stores only the returned reference."
    ))
    story.append(code_block(
        "<m:createApprovedDispensingRequest xmlns:m=\"urn:medicare:patient-record\">\n"
        "  <m:requestID>REQ-9F21</m:requestID><m:timestamp>2026-09-04 14:12:00</m:timestamp>\n"
        "  <m:prescriptionReference>RX-3A8D6F91</m:prescriptionReference>\n"
        "  <m:patientExternalId>PA002</m:patientExternalId><m:patientName>Aisyah Rahman</m:patientName>\n"
        "  <m:prescriberExternalId>DC001</m:prescriberExternalId><m:prescriberName>Doctor DC001</m:prescriberName>\n"
        "  <m:items>\n"
        "    <m:item><m:sku>MED-PARA-500</m:sku><m:quantity>10</m:quantity>\n"
        "      <m:instructions>Take after food.</m:instructions></m:item>\n"
        "    <m:item><m:sku>MED-SALB-100</m:sku><m:quantity>1</m:quantity>\n"
        "      <m:instructions>Use when required.</m:instructions></m:item>\n"
        "  </m:items>\n"
        "</m:createApprovedDispensingRequest>"
    ))
    story.append(caption("XML Example 4. Multiple medicines in one approved dispensing request."))
    story.append(PageBreak())

    story.append(H2("6.6 Cross-module data ownership"))
    story.append(
        table(
            [
                ["Module", "Database it owns", "Patient Record obtains external data by"],
                ["Patient Record", "medicare_connect2", "Direct Eloquent access using patient_record_app"],
                ["Pharmacy", "medicare_pharmacy", "Pharmacy REST/XML endpoint only"],
                ["User Management / Appointment assignment", "clinic_db / teammate-owned schema", "Shared authenticated session and doctor-patient assignment REST/XML endpoint"],
            ],
            [46 * mm, 45 * mm, 69 * mm],
        )
    )
    story.append(P(
        "This separation proves that the service integration is necessary: the Patient Record database account has no "
        "permission to select from Pharmacy or User Management tables. If an external module is unavailable, the Patient "
        "Record code handles the failure instead of silently bypassing the service boundary."
    ))
    story.append(H2("6.7 REST/XML implementation extract"))
    story.append(code_block(
        "$client = curl_init($serviceUrl);\n"
        "curl_setopt_array($client, [\n"
        "  CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestXml,\n"
        "  CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 5,\n"
        "  CURLOPT_HTTPHEADER => [\n"
        "    'Content-Type: application/xml', 'Accept: application/xml', 'X-API-Key: ' . $apiKey\n"
        "  ],\n"
        "]);\n"
        "$body = curl_exec($client);"
    ))
    story.append(caption("Code Extract 7. REST transport used by PharmacyServiceClient."))
    story.append(P(
        "After transport, the client rejects oversized, unsafe, malformed, unexpected-root, or XSD-invalid XML. It then "
        "checks mandatory fields, status, requestID correlation, item identifiers, and operation-specific success fields."
    ))
    story.append(H2("6.8 Automated integration and security tests"))
    test_rows = [
        ["Suite", "Verified scenarios", "Result"],
        ["Application security", "Missing identity; session contract; doctor session; account switching; patient identity cleanup; valid/invalid CSRF; encrypt/decrypt/tamper; three decorators", "13/13 PASS"],
        ["Pharmacy REST/XML", "Catalogue; available/unavailable; invalid SKU; multiple medicines; approved creation; duplicate/idempotent reference; unavailable service; malformed XML; wrong API key; Pharmacy success plus local failure", "11/11 PASS"],
        ["Patient Record XML exposure", "HTTP 200/S; no clinical remark disclosure; invalid XML; missing key; unsupported method; stale timestamp; unknown patient", "8/8 PASS"],
    ]
    story.append(table(test_rows, [38 * mm, 94 * mm, 28 * mm], font_size=7.3))
    story.append(P(
        "The Pharmacy tests use controlled transport doubles, so success and failure responses can be reproduced without "
        "changing live stock. The XML smoke test exercises the actual service class and endpoint wrapper. These tests show "
        "normal, boundary, authentication, malformed-input, dependency-failure, idempotency, and partial-failure behaviour."
    ))
    story.append(PageBreak())

    # Reflection
    story.append(H2("6.9 Reflection and Independent Learning"))
    story.append(H3("6.9.1 Knowledge developed"))
    story.append(P(
        "This module required more than implementing CRUD forms. I learned how to use Eloquent outside a full Laravel "
        "application, model object relationships, and apply an authenticated-encryption transformation at the entity "
        "boundary. I also learned that a design-pattern label is not itself a security feature: the ConfidentialityDecorator "
        "communicates classification, while access control and encryption provide actual protection."
    ))
    story.append(P(
        "The integration work showed why database sharing creates tight coupling. Restricting each database account forced "
        "the Patient Record module to use explicit contracts. Request IDs detect mismatched responses, timestamps limit "
        "replay windows, XSD catches structural mistakes, and deterministic error status values help other modules respond "
        "correctly. The Pharmacy-success/local-failure case also demonstrated that distributed operations cannot rely on a "
        "single MySQL transaction; they require reconciliation evidence."
    ))
    story.append(H3("6.9.2 Problems solved and decisions"))
    story.append(B("The original vendor autoloader was incomplete. Composer dependencies were restored so Eloquent could load reliably."))
    story.append(B("Patients were initially able to reach edit behaviour. Role and record-ownership rules now ensure patients are read-only."))
    story.append(B("An edit originally risked changing the patient owner. Update now reuses the existing record's patient_id and never accepts ownership from the form."))
    story.append(B("Doctor lists originally depended only on existing records, preventing the first record. REST assignment data now provides the authorised patient before a record exists."))
    story.append(B("Medicine quantity was initially only a browser constraint. Server-side Pharmacy availability is now authoritative and is rechecked immediately before saving."))
    story.append(B("Decorator output originally appeared as a second debug panel. Its effects are now integrated into the normal detail view, and audit evidence is stored in the database."))
    story.append(H3("6.9.3 Current limitations and future work"))
    story.append(P(
        "The development environment uses HTTP localhost, so the Secure session-cookie flag becomes effective only when "
        "the final deployment uses HTTPS. A production system should use managed key storage with rotation, structured "
        "central logging, rate limiting on APIs, and a formal compensating action in Pharmacy rather than only logging a "
        "reconciliation reference. End-to-end tests across all teammates' deployed modules should also run in a shared "
        "integration environment before submission."
    ))
    story.append(P(
        "The supplied team class diagram includes earlier Appointment fields that are no longer stored in the Patient Record "
        "database. This report therefore presents the implemented Patient Record entity model, while appointment assignment "
        "remains external service data. This keeps the documentation consistent with the submitted source code."
    ))
    story.append(PageBreak())

    # References
    story.append(H1("7. References"))
    references = [
        "Fielding, R. T. (2000). <i>Architectural styles and the design of network-based software architectures</i> (Doctoral dissertation, University of California, Irvine). https://www.ics.uci.edu/~fielding/pubs/dissertation/top.htm",
        "Gamma, E., Helm, R., Johnson, R., & Vlissides, J. (1994). <i>Design patterns: Elements of reusable object-oriented software</i>. Addison-Wesley.",
        "Laravel. (2026). <i>Eloquent ORM: Getting started (Laravel 12.x)</i>. https://laravel.com/framework/docs/12.x/eloquent",
        "OpenAI. (2026). <i>ChatGPT and Codex</i> [Large language model]. https://openai.com/codex/",
        "OWASP Foundation. (2026a). <i>Cryptographic storage cheat sheet</i>. https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html",
        "OWASP Foundation. (2026b). <i>SQL injection prevention cheat sheet</i>. https://cheatsheetseries.owasp.org/cheatsheets/SQL_Injection_Prevention_Cheat_Sheet.html",
        "PHP Documentation Group. (2026). <i>Prepared statements and stored procedures</i>. https://www.php.net/manual/en/pdo.prepared-statements.php",
        "United Nations. (2026). <i>Goal 3: Ensure healthy lives and promote well-being for all at all ages</i>. https://sdgs.un.org/goals/goal3",
        "World Wide Web Consortium. (2012). <i>W3C XML Schema Definition Language (XSD) 1.1 Part 1: Structures</i>. https://www.w3.org/TR/xmlschema11-1/",
    ]
    for ref in references:
        story.append(P(ref, "ReferenceReport"))
    story.append(H2("7.1 Source-code evidence index"))
    story.append(
        table(
            [
                ["Requirement", "Primary evidence in submission"],
                ["MVC", "index.php; Controller/; Model/; View/"],
                ["ORM", "Shared/orm.php; composer.json; Model/*Entity.php"],
                ["Decorator", "Pattern/PatientRecordSummary/; PatientRecordController::show()"],
                ["SQL injection control", "Eloquent model queries; typed input validation in controllers"],
                ["Encrypted stored data", "Shared/SensitiveDataCipher.php; PatientRecordEntity::remark()"],
                ["REST exposure", "api/patient-record-summary.php; WebService/PatientRecordXmlService.php"],
                ["REST consumption", "Service/PharmacyServiceClient.php; Service/PatientRecordCreationService.php"],
                ["XML/XSD", "WebService/xsd/; Service/xsd/; WebService/examples/; Service/examples/"],
                ["SQL scripts", "Database/schema.sql; Database/sample_data.sql; Database/migrations/"],
                ["Automated tests", "tests/application_security_test.php; tests/pharmacy_integration_test.php; tests/xml_service_smoke.php"],
            ],
            [52 * mm, 108 * mm],
        )
    )
    story.append(Spacer(1, 8 * mm))
    story.append(P(
        "<b>Final submission reminder:</b> the PDF should be named with the student's full name. Submit the report together "
        "with the source code and SQL create/populate scripts, and be ready to demonstrate the running module and explain "
        "the design, security controls, ORM relationships, and REST request/response flow.", "Callout"
    ))
    return story


def main():
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = NumberedReport(str(OUTPUT))
    doc.multiBuild(build_story())
    print(OUTPUT)


if __name__ == "__main__":
    main()
