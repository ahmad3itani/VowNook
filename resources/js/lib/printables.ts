/**
 * Print-ready PDF generators for day-of stationery, built from the seating +
 * meal data already on the seating page. All client-side (jsPDF), reusing the
 * same dynamic-import pattern as the seating chart export.
 */

type Guest = { id: number; name: string; table_id: number | null; meal_choice: string | null };
type Table = { id: number; name: string };
type MenuCourse = { course: string; label: string; options: string[] };

const GOLD: [number, number, number] = [0x8a, 0x65, 0x1c];
const INK: [number, number, number] = [0x1e, 0x1b, 0x17];
const MUTED: [number, number, number] = [0x6b, 0x60, 0x52];
const LINE: [number, number, number] = [0xcf, 0xc5, 0xb8];

async function newDoc(orientation: 'portrait' | 'landscape') {
    const { jsPDF } = await import('jspdf');
    return new jsPDF({ orientation, unit: 'mm', format: 'a4' });
}

/** Light dashed cut-guides around a card rectangle. */
function cutBorder(doc: any, x: number, y: number, w: number, h: number) {
    doc.setDrawColor(...LINE);
    doc.setLineWidth(0.1);
    doc.setLineDashPattern([1.2, 1.2], 0);
    doc.rect(x, y, w, h);
    doc.setLineDashPattern([], 0);
}

/**
 * Lay out small cards in a grid across A4 portrait pages, calling `draw` for
 * each item with the card's box. cols×rows per page.
 */
function cardGrid<T>(
    doc: any,
    items: T[],
    cols: number,
    rows: number,
    draw: (item: T, x: number, y: number, w: number, h: number) => void,
) {
    const pageW = 210;
    const pageH = 297;
    const margin = 12;
    const gridW = pageW - margin * 2;
    const gridH = pageH - margin * 2;
    const cardW = gridW / cols;
    const cardH = gridH / rows;
    const perPage = cols * rows;

    items.forEach((item, i) => {
        if (i > 0 && i % perPage === 0) doc.addPage();
        const slot = i % perPage;
        const col = slot % cols;
        const row = Math.floor(slot / cols);
        const x = margin + col * cardW;
        const y = margin + row * cardH;
        cutBorder(doc, x, y, cardW, cardH);
        draw(item, x, y, cardW, cardH);
    });
}

function tableNameFor(tables: Table[], id: number | null): string | null {
    if (id == null) return null;
    return tables.find((t) => t.id === id)?.name ?? null;
}

/** Escort cards — one per seated guest: name + the table to find. 2×5 per page. */
export async function escortCards(weddingName: string, guests: Guest[], tables: Table[]) {
    const seated = guests
        .filter((g) => g.table_id != null)
        .map((g) => ({ name: g.name, table: tableNameFor(tables, g.table_id) }))
        .sort((a, b) => a.name.localeCompare(b.name));

    if (seated.length === 0) throw new Error('No guests are assigned to a table yet.');

    const doc = await newDoc('portrait');
    cardGrid(doc, seated, 2, 5, (g, x, y, w, h) => {
        const cx = x + w / 2;
        doc.setTextColor(...MUTED);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.text(weddingName.toUpperCase(), cx, y + 11, { align: 'center' });

        doc.setTextColor(...INK);
        doc.setFont('times', 'normal');
        doc.setFontSize(20);
        doc.text(g.name, cx, y + h / 2 + 1, { align: 'center' });

        doc.setTextColor(...GOLD);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.text(g.table ? `Table ${g.table}` : 'To be seated', cx, y + h - 12, { align: 'center' });
    });
    doc.save('escort-cards.pdf');
}

/** Place cards — name + meal, one per seated guest, foldable. 2×5 per page. */
export async function placeCards(weddingName: string, guests: Guest[], tables: Table[]) {
    const seated = guests
        .filter((g) => g.table_id != null)
        .map((g) => ({ name: g.name, meal: g.meal_choice, table: tableNameFor(tables, g.table_id) }))
        .sort((a, b) => a.name.localeCompare(b.name));

    if (seated.length === 0) throw new Error('No guests are assigned to a table yet.');

    const doc = await newDoc('portrait');
    cardGrid(doc, seated, 2, 5, (g, x, y, w, h) => {
        const cx = x + w / 2;
        doc.setTextColor(...INK);
        doc.setFont('times', 'normal');
        doc.setFontSize(19);
        doc.text(g.name, cx, y + h / 2 - 2, { align: 'center' });

        doc.setDrawColor(...GOLD);
        doc.setLineWidth(0.3);
        doc.line(cx - 12, y + h / 2 + 3, cx + 12, y + h / 2 + 3);

        if (g.meal) {
            doc.setTextColor(...MUTED);
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.text(g.meal, cx, y + h / 2 + 11, { align: 'center' });
        }
    });
    doc.save('place-cards.pdf');
}

/** Table number cards — one big card per table. 2×2 per page. */
export async function tableNumberCards(weddingName: string, tables: Table[]) {
    if (tables.length === 0) throw new Error('Add some tables first.');

    const sorted = [...tables].sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true }));
    const doc = await newDoc('portrait');
    cardGrid(doc, sorted, 2, 2, (t, x, y, w, h) => {
        const cx = x + w / 2;
        doc.setTextColor(...MUTED);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.text(weddingName.toUpperCase(), cx, y + 18, { align: 'center' });

        doc.setTextColor(...GOLD);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.text('TABLE', cx, y + h / 2 - 14, { align: 'center' });

        doc.setTextColor(...INK);
        doc.setFont('times', 'normal');
        doc.setFontSize(54);
        doc.text(t.name, cx, y + h / 2 + 12, { align: 'center' });

        doc.setTextColor(...MUTED);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.text('Please find your seat', cx, y + h - 14, { align: 'center' });
    });
    doc.save('table-numbers.pdf');
}

/** Menu card — the couple's courses + options, one elegant A4 per copy. */
export async function menuCard(weddingName: string, menu: MenuCourse[]) {
    if (menu.length === 0) throw new Error('Set up your meal options on the Guests page first.');

    const doc = await newDoc('portrait');
    const cx = 105;

    doc.setDrawColor(...GOLD);
    doc.setLineWidth(0.4);
    doc.rect(18, 18, 174, 261);

    doc.setTextColor(...MUTED);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);
    doc.text(weddingName.toUpperCase(), cx, 48, { align: 'center' });

    doc.setTextColor(...INK);
    doc.setFont('times', 'normal');
    doc.setFontSize(40);
    doc.text('Menu', cx, 70, { align: 'center' });

    doc.setDrawColor(...GOLD);
    doc.setLineWidth(0.3);
    doc.line(cx - 15, 78, cx + 15, 78);

    let yy = 100;
    for (const course of menu) {
        doc.setTextColor(...GOLD);
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(11);
        doc.text(course.label.toUpperCase(), cx, yy, { align: 'center' });
        yy += 9;

        doc.setTextColor(...INK);
        doc.setFont('times', 'normal');
        doc.setFontSize(14);
        const options = course.options.length > 0 ? course.options : ['Chef’s selection'];
        for (const opt of options) {
            doc.text(opt, cx, yy, { align: 'center' });
            yy += 8;
        }
        yy += 8;
    }

    doc.save('menu.pdf');
}
