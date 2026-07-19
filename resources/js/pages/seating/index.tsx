import { toPng } from 'html-to-image';
import { Head, router, useForm } from '@inertiajs/react';
import {
    Armchair,
    Bath,
    Box,
    Cake,
    Camera,
    Disc3,
    DoorOpen,
    ClipboardList,
    FileDown,
    Gift,
    GripVertical,
    Maximize2,
    Mic,
    Music,
    Pencil,
    Plus,
    Printer,
    RotateCw,
    Search,
    Trash2,
    Utensils,
    Wine,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SeatingInfographics, type SeatingStats } from '@/components/seating/seating-infographics';
import { escortCards, placeCards, tableNumberCards, menuCard } from '@/lib/printables';
import { usePermissions } from '@/hooks/use-permissions';

type Option = { value: string; label: string };
type ElementTypeOption = { value: string; label: string; size: [number, number] };

type Table = {
    id: number;
    name: string;
    shape: string;
    capacity: number;
    position_x: number;
    position_y: number;
    notes: string | null;
    seated: number;
};

type SeatGuest = {
    id: number;
    name: string;
    table_id: number | null;
    seat_number: number | null;
    rsvp_status: string;
    meal_choice: string | null;
    dietary_notes: string | null;
};

type FloorElement = {
    id: number;
    type: string;
    label: string;
    position_x: number;
    position_y: number;
    width: number;
    height: number;
    rotation: number;
};

type Stats = SeatingStats;

type MenuCourse = { course: string; label: string; options: string[] };

type PageProps = {
    weddingName: string;
    menu: MenuCourse[];
    tables: Table[];
    guests: SeatGuest[];
    elements: FloorElement[];
    layout: { room_width: number; room_height: number };
    stats: Stats;
    options: { shapes: Option[]; elementTypes: ElementTypeOption[] };
};

const ELEMENT_ICON: Record<string, LucideIcon> = {
    dance_floor: Disc3,
    bar: Wine,
    dj_booth: Music,
    stage: Mic,
    gift_table: Gift,
    cake_table: Cake,
    photo_booth: Camera,
    buffet: Utensils,
    entrance: DoorOpen,
    restroom: Bath,
    other: Box,
};

const RSVP_RING: Record<string, string> = {
    attending: 'ring-[#1b4638]',
    maybe: 'ring-[#6e9e8a]',
    declined: 'ring-[#c1b6a8]',
    pending: 'ring-[#e0d6c9]',
};

function clamp(value: number, min: number, max: number) {
    return Math.min(max, Math.max(min, value));
}

function initials(name: string) {
    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase() ?? '')
        .join('');
}

/** Geometry for a table and the (x,y) px offset of each chair from its centre. */
function tableGeometry(shape: string, capacity: number, scale: number) {
    const chair = 22 * scale;
    const cap = Math.max(1, capacity);

    if (shape === 'rectangle' || shape === 'square') {
        const perRow = Math.ceil(cap / 2);
        const tableW =
            shape === 'square'
                ? Math.max(54, perRow * (chair + 6)) * 0.8
                : Math.max(60, perRow * (chair + 8));
        const tableH = shape === 'square' ? tableW : 40 * scale;

        const seats: { n: number; x: number; y: number }[] = [];
        const top = Math.ceil(cap / 2);
        const bottom = cap - top;
        const place = (count: number, y: number, startN: number) => {
            for (let i = 0; i < count; i++) {
                const t = count === 1 ? 0.5 : i / (count - 1);
                const x = (t - 0.5) * (tableW - chair);
                seats.push({ n: startN + i, x, y });
            }
        };
        place(top, -(tableH / 2 + chair * 0.7), 1);
        place(bottom, tableH / 2 + chair * 0.7, top + 1);

        return { tableW, tableH, chair, seats };
    }

    // Round: chairs evenly spread on a ring; radius grows to avoid overlap.
    const minRadius = (cap * (chair + 6)) / (2 * Math.PI);
    const radius = Math.max(30 * scale, minRadius);
    const diameter = Math.max(48 * scale, radius * 1.15);
    const seats = Array.from({ length: cap }, (_, i) => {
        const angle = -Math.PI / 2 + (i / cap) * 2 * Math.PI;

        return {
            n: i + 1,
            x: Math.cos(angle) * (radius + chair * 0.55),
            y: Math.sin(angle) * (radius + chair * 0.55),
        };
    });

    return { tableW: diameter, tableH: diameter, chair, seats };
}

/** Resolve which guest sits in each seat number, filling blanks in order. */
function resolveSeats(occupants: SeatGuest[], capacity: number) {
    const map = new Map<number, SeatGuest>();
    const leftovers: SeatGuest[] = [];

    for (const g of occupants) {
        if (g.seat_number && g.seat_number >= 1 && g.seat_number <= capacity && !map.has(g.seat_number)) {
            map.set(g.seat_number, g);
        } else {
            leftovers.push(g);
        }
    }

    let n = 1;

    for (const g of leftovers) {
        while (n <= capacity && map.has(n)) {
n++;
}

        if (n > capacity) {
break;
}

        map.set(n, g);
    }

    return map;
}

type TableFormData = { name: string; shape: string; capacity: string; notes: string };

function emptyForm(options: PageProps['options']): TableFormData {
    return { name: '', shape: options.shapes[0]?.value ?? 'round', capacity: '8', notes: '' };
}

type Drag =
    | { kind: 'move-table'; id: number }
    | { kind: 'move-element'; id: number }
    | { kind: 'resize-element'; id: number; startX: number; startY: number; startW: number; startH: number };

export default function SeatingIndex({ weddingName, menu, tables, guests, elements, layout, stats, options }: PageProps) {
    const { canWrite } = usePermissions();
    const writable = canWrite('seating');

    const canvasRef = useRef<HTMLDivElement>(null);
    const [exporting, setExporting] = useState(false);
    const [exportingFnb, setExportingFnb] = useState(false);

    async function exportFloorPlan() {
        if (!canvasRef.current || exporting) return;
        setExporting(true);
        try {
            // Capture the canvas at 2× for crisp print quality
            const dataUrl = await toPng(canvasRef.current, {
                pixelRatio: 2,
                backgroundColor: '#e4e8e0',
            });

            const [{ jsPDF }, { default: autoTable }] = await Promise.all([
                import('jspdf'),
                import('jspdf-autotable'),
            ]);

            const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const pageW = doc.internal.pageSize.getWidth();
            const pageH = doc.internal.pageSize.getHeight();
            const margin = 10;
            const gold: [number, number, number] = [119, 90, 25];
            const dark: [number, number, number] = [30, 27, 23];

            // ── Page 1: floor plan screenshot ──────────────────────────
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor(...dark);
            doc.text(weddingName, margin, 13);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...gold);
            doc.text(`FLOOR PLAN  ·  ${layout.room_width} × ${layout.room_height} FT`, margin, 19);

            // Fit the screenshot to the remaining page area
            const imgStartY = 23;
            const maxImgW = pageW - margin * 2;
            const maxImgH = pageH - imgStartY - margin;

            const imgEl = new Image();
            imgEl.src = dataUrl;
            await new Promise<void>((res) => { imgEl.onload = () => res(); });
            const ratio = imgEl.naturalWidth / imgEl.naturalHeight;
            let imgW = maxImgW;
            let imgH = imgW / ratio;
            if (imgH > maxImgH) { imgH = maxImgH; imgW = imgH * ratio; }

            doc.addImage(dataUrl, 'PNG', margin, imgStartY, imgW, imgH);

            // ── Page 2: seating chart table ─────────────────────────────
            doc.addPage();

            doc.setFont('helvetica', 'bold');
            doc.setFontSize(15);
            doc.setTextColor(...dark);
            doc.text(weddingName, margin, 13);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(7);
            doc.setTextColor(...gold);
            doc.text('SEATING CHART', margin, 19);

            let cursorY = 24;

            for (const table of tables) {
                const seated = guests
                    .filter((g) => g.table_id === table.id)
                    .sort((a, b) => (a.seat_number ?? 999) - (b.seat_number ?? 999));

                const body = seated.map((g) => [
                    g.seat_number ?? '·',
                    g.name,
                    g.meal_choice ?? '—',
                    g.dietary_notes ?? '—',
                ]);

                autoTable(doc, {
                    startY: cursorY,
                    head: [[
                        { content: `${table.name}  ·  ${table.shape} · ${seated.length}/${table.capacity}`, colSpan: 4 },
                    ], ['Seat', 'Guest', 'Meal', 'Allergies / dietary']],
                    body: body.length ? body : [['', 'No one seated yet.', '', '']],
                    theme: 'plain',
                    styles: { fontSize: 8, cellPadding: 2, textColor: dark },
                    alternateRowStyles: { fillColor: [253, 249, 243] },
                    columnStyles: {
                        0: { cellWidth: 12, halign: 'center' },
                        1: { cellWidth: 60 },
                        2: { cellWidth: 55 },
                        3: { cellWidth: 'auto' },
                    },
                    margin: { left: margin, right: margin },
                    didParseCell: (data) => {
                        if (data.section === 'head') {
                            if (data.row.index === 0) {
                                data.cell.styles.fillColor = [253, 245, 230];
                                data.cell.styles.textColor = gold;
                                data.cell.styles.fontStyle = 'bold';
                                data.cell.styles.fontSize = 9;
                            } else {
                                data.cell.styles.fillColor = [240, 235, 225];
                                data.cell.styles.textColor = [111, 103, 94];
                                data.cell.styles.fontSize = 7;
                            }
                        }
                    },
                    didDrawPage: () => { cursorY = margin; },
                });

                cursorY = (doc as any).lastAutoTable.finalY + 5;
            }

            // Unseated guests
            const unseated = guests.filter((g) => g.table_id === null);
            if (unseated.length) {
                autoTable(doc, {
                    startY: cursorY,
                    head: [[{ content: `Not yet seated  ·  ${unseated.length}`, colSpan: 2 }]],
                    body: unseated.map((g) => [g.name]),
                    theme: 'plain',
                    styles: { fontSize: 8, cellPadding: 2, textColor: dark },
                    headStyles: { fillColor: [253, 245, 230], textColor: gold, fontStyle: 'bold', fontSize: 8 },
                    margin: { left: margin, right: margin },
                });
            }

            const slug = weddingName.toLowerCase().replace(/\s+/g, '-');
            doc.save(`${slug}-seating-chart.pdf`);
        } catch (err) {
            console.error('[exportFloorPlan]', err);
            toast.error(`Export failed: ${err instanceof Error ? err.message : String(err)}`);
        } finally {
            setExporting(false);
        }
    }

    async function exportFnbSheet() {
        if (exportingFnb) return;
        setExportingFnb(true);
        try {
            const [{ jsPDF }, { default: autoTable }] = await Promise.all([
                import('jspdf'),
                import('jspdf-autotable'),
            ]);

            const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            const pageW = doc.internal.pageSize.getWidth();
            const margin = 10;
            const gold: [number, number, number] = [119, 90, 25];
            const dark: [number, number, number] = [30, 27, 23];
            const red: [number, number, number] = [180, 60, 50];
            const cream: [number, number, number] = [253, 245, 230];
            const lightCream: [number, number, number] = [253, 249, 243];
            const muted: [number, number, number] = [111, 103, 94];

            // ── helpers ────────────────────────────────────────────────
            function pageHeader(doc: InstanceType<typeof jsPDF>, subtitle: string) {
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(15);
                doc.setTextColor(...dark);
                doc.text(weddingName, margin, 13);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(7);
                doc.setTextColor(...gold);
                doc.text(subtitle, margin, 19);
                doc.setTextColor(...dark);
            }

            // Collect all distinct meal choices from seated guests
            const seatedGuests = guests.filter((g) => g.table_id !== null);
            const allMealTypes = [...new Set(
                seatedGuests.map((g) => g.meal_choice?.trim() ?? '').filter(Boolean)
            )].sort();
            const noMealCount = seatedGuests.filter((g) => !g.meal_choice?.trim()).length;

            // Per-table meal data
            const tableData = tables.map((table) => {
                const seated = guests
                    .filter((g) => g.table_id === table.id)
                    .sort((a, b) => (a.seat_number ?? 999) - (b.seat_number ?? 999));
                const mealCounts: Record<string, number> = {};
                for (const g of seated) {
                    const key = g.meal_choice?.trim() || '(no selection)';
                    mealCounts[key] = (mealCounts[key] ?? 0) + 1;
                }
                return { table, seated, mealCounts };
            });

            // ── PAGE 1: Kitchen Summary ────────────────────────────────
            pageHeader(doc, 'F&B SERVICE SHEET  ·  KITCHEN SUMMARY');

            // Overall totals row
            const totalByMeal: Record<string, number> = {};
            for (const g of seatedGuests) {
                const key = g.meal_choice?.trim() || '(no selection)';
                totalByMeal[key] = (totalByMeal[key] ?? 0) + 1;
            }

            // Big summary table
            const summaryHead = [['Meal', 'Total', '% of guests']];
            const summaryBody = Object.entries(totalByMeal)
                .sort((a, b) => b[1] - a[1])
                .map(([meal, count]) => [
                    meal,
                    String(count),
                    seatedGuests.length ? `${Math.round((count / seatedGuests.length) * 100)}%` : '—',
                ]);

            autoTable(doc, {
                startY: 24,
                head: summaryHead,
                body: summaryBody.length ? summaryBody : [['No meal data', '—', '—']],
                theme: 'plain',
                styles: { fontSize: 10, cellPadding: 3, textColor: dark },
                headStyles: { fillColor: gold, textColor: [255, 255, 255] as [number,number,number], fontStyle: 'bold', fontSize: 9 },
                alternateRowStyles: { fillColor: lightCream },
                columnStyles: {
                    0: { cellWidth: 'auto', fontStyle: 'bold' },
                    1: { cellWidth: 25, halign: 'center', fontSize: 14, fontStyle: 'bold' },
                    2: { cellWidth: 30, halign: 'center' },
                },
                margin: { left: margin, right: margin },
            });

            // Per-table meal breakdown
            const breakdownStartY = (doc as any).lastAutoTable.finalY + 10;
            const mealCols = allMealTypes.length ? allMealTypes : ['(no selection)'];
            const breakdownHead = [['Table', 'Seated', ...mealCols]];
            const breakdownBody = tableData.map(({ table, seated, mealCounts }) => [
                table.name,
                String(seated.length),
                ...mealCols.map((m) => (mealCounts[m] ? String(mealCounts[m]) : '—')),
            ]);

            // Totals row
            breakdownBody.push([
                'TOTAL',
                String(seatedGuests.length),
                ...mealCols.map((m) => String(totalByMeal[m] ?? 0)),
            ]);

            autoTable(doc, {
                startY: breakdownStartY,
                head: breakdownHead,
                body: breakdownBody,
                theme: 'grid',
                styles: { fontSize: 8, cellPadding: 2, textColor: dark },
                headStyles: { fillColor: cream, textColor: gold, fontStyle: 'bold', fontSize: 8 },
                alternateRowStyles: { fillColor: lightCream },
                didParseCell: (data) => {
                    // Bold the totals row
                    if (data.section === 'body' && data.row.index === breakdownBody.length - 1) {
                        data.cell.styles.fontStyle = 'bold';
                        data.cell.styles.fillColor = cream;
                    }
                },
                margin: { left: margin, right: margin },
            });

            // ── PAGE 2: Runner Sheets ──────────────────────────────────
            doc.addPage();
            pageHeader(doc, 'F&B SERVICE SHEET  ·  RUNNER REFERENCE');

            let cursorY = 24;

            for (const { table, seated } of tableData) {
                const hasAllergy = seated.some((g) => g.dietary_notes?.trim());
                const tableLabel = `${table.name.toUpperCase()}  ·  ${seated.length}/${table.capacity} SEATED${hasAllergy ? '  ⚠ ALLERGY' : ''}`;

                const runnerBody = seated.map((g) => [
                    g.seat_number ?? '·',
                    g.name,
                    g.meal_choice?.trim() || '—',
                    g.dietary_notes?.trim() || '',
                ]);

                if (runnerBody.length === 0) continue;

                autoTable(doc, {
                    startY: cursorY,
                    head: [
                        [{ content: tableLabel, colSpan: 4 }],
                        ['Seat', 'Guest', 'Meal', 'Dietary / allergies'],
                    ],
                    body: runnerBody,
                    theme: 'plain',
                    styles: { fontSize: 8, cellPadding: 2, textColor: dark },
                    alternateRowStyles: { fillColor: lightCream },
                    columnStyles: {
                        0: { cellWidth: 12, halign: 'center', fontStyle: 'bold' },
                        1: { cellWidth: 60 },
                        2: { cellWidth: 60 },
                        3: { cellWidth: 'auto' },
                    },
                    margin: { left: margin, right: margin },
                    didParseCell: (data) => {
                        if (data.section === 'head') {
                            if (data.row.index === 0) {
                                data.cell.styles.fillColor = hasAllergy ? [255, 243, 230] : cream;
                                data.cell.styles.textColor = hasAllergy ? red : gold;
                                data.cell.styles.fontStyle = 'bold';
                                data.cell.styles.fontSize = 8;
                            } else {
                                data.cell.styles.fillColor = [240, 235, 225];
                                data.cell.styles.textColor = muted;
                                data.cell.styles.fontSize = 7;
                            }
                        }
                        // Highlight allergy rows
                        if (data.section === 'body' && data.column.index === 3) {
                            const row = data.row.raw as (string | number)[];
                            if (row[3]) {
                                data.cell.styles.textColor = red;
                                data.cell.styles.fontStyle = 'bold';
                            }
                        }
                    },
                    didDrawPage: () => { cursorY = margin; },
                });

                cursorY = (doc as any).lastAutoTable.finalY + 4;
            }

            // ── PAGE 3: Allergy Alerts ─────────────────────────────────
            const allergyGuests = guests.filter((g) => g.dietary_notes?.trim());
            if (allergyGuests.length) {
                doc.addPage();
                pageHeader(doc, `F&B SERVICE SHEET  ·  ALLERGY & DIETARY ALERTS  ·  ${allergyGuests.length} GUESTS`);

                // Warning banner
                doc.setFillColor(255, 235, 230);
                doc.rect(margin, 22, pageW - margin * 2, 8, 'F');
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(8);
                doc.setTextColor(...red);
                doc.text('⚠  VERIFY EVERY FLAGGED GUEST PERSONALLY BEFORE SERVICE. DO NOT SWAP PLATES.', margin + 3, 27.5);

                const allergyBody = allergyGuests
                    .sort((a, b) => {
                        const ta = tables.find((t) => t.id === a.table_id)?.name ?? 'ZZZ';
                        const tb = tables.find((t) => t.id === b.table_id)?.name ?? 'ZZZ';
                        return ta.localeCompare(tb) || (a.seat_number ?? 999) - (b.seat_number ?? 999);
                    })
                    .map((g) => [
                        tables.find((t) => t.id === g.table_id)?.name ?? '—',
                        g.seat_number ?? '·',
                        g.name,
                        g.meal_choice?.trim() || '—',
                        g.dietary_notes?.trim() ?? '',
                    ]);

                autoTable(doc, {
                    startY: 33,
                    head: [['Table', 'Seat', 'Guest', 'Meal', 'Dietary / allergies']],
                    body: allergyBody,
                    theme: 'plain',
                    styles: { fontSize: 9, cellPadding: 3, textColor: dark },
                    headStyles: { fillColor: [255, 220, 210] as [number,number,number], textColor: red, fontStyle: 'bold', fontSize: 8 },
                    alternateRowStyles: { fillColor: [255, 248, 245] },
                    columnStyles: {
                        0: { cellWidth: 40, fontStyle: 'bold' },
                        1: { cellWidth: 12, halign: 'center' },
                        2: { cellWidth: 55 },
                        3: { cellWidth: 50 },
                        4: { cellWidth: 'auto', textColor: red, fontStyle: 'bold' },
                    },
                    margin: { left: margin, right: margin },
                    didParseCell: (data) => {
                        if (data.section === 'body') {
                            data.cell.styles.fillColor = data.row.index % 2 === 0
                                ? [255, 248, 245]
                                : [255, 255, 255];
                        }
                    },
                });
            }

            const slug = weddingName.toLowerCase().replace(/\s+/g, '-');
            doc.save(`${slug}-fnb-sheet.pdf`);
        } catch (err) {
            console.error('[exportFnbSheet]', err);
            toast.error(`Export failed: ${err instanceof Error ? err.message : String(err)}`);
        } finally {
            setExportingFnb(false);
        }
    }

    // Optimistic positions/sizes while dragging.
    const [tablePos, setTablePos] = useState<Record<number, { x: number; y: number }>>({});
    const [elemPos, setElemPos] = useState<Record<number, { x: number; y: number }>>({});
    const [elemSize, setElemSize] = useState<Record<number, { width: number; height: number }>>({});
    const [drag, setDrag] = useState<Drag | null>(null);

    const [dragGuestId, setDragGuestId] = useState<number | null>(null);
    const [dropTarget, setDropTarget] = useState<string | null>(null);
    const [selectedElement, setSelectedElement] = useState<number | null>(null);
    const [selectedTableId, setSelectedTableId] = useState<number | null>(null);
    const [guestSearch, setGuestSearch] = useState('');
    // Tap-to-assign (touch-friendly: drag-and-drop doesn't fire on phones).
    const [selectedGuestId, setSelectedGuestId] = useState<number | null>(null);

    const [sheetOpen, setSheetOpen] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const [newElementType, setNewElementType] = useState(options.elementTypes[0]?.value ?? 'dance_floor');

    const form = useForm<TableFormData>(emptyForm(options));

    const scale = clamp(46 / layout.room_width, 0.55, 1.3);
    const aspect = `${layout.room_width} / ${layout.room_height}`;

    const unseated = guests.filter((g) => g.table_id === null);
    const filteredUnseated = unseated.filter((g) =>
        g.name.toLowerCase().includes(guestSearch.trim().toLowerCase()),
    );
    const selectedTable = tables.find((t) => t.id === selectedTableId) ?? null;
    const selectedElementObj = elements.find((e) => e.id === selectedElement) ?? null;

    function clearSelection() {
        setSelectedElement(null);
        setSelectedTableId(null);
    }

    const tablePosFor = (t: Table) => tablePos[t.id] ?? { x: t.position_x, y: t.position_y };
    const elemPosFor = (e: FloorElement) => elemPos[e.id] ?? { x: e.position_x, y: e.position_y };
    const elemSizeFor = (e: FloorElement) => elemSize[e.id] ?? { width: e.width, height: e.height };

    const persistTable = useCallback(
        (id: number, x: number, y: number) => {
            router.patch(
                `/seating/${id}/move`,
                { position_x: Math.round(x), position_y: Math.round(y) },
                { preserveScroll: true, preserveState: true },
            );
        },
        [],
    );

    const persistElement = useCallback(
        (id: number, payload: Record<string, number>) => {
            router.patch(`/seating-elements/${id}/move`, payload, {
                preserveScroll: true,
                preserveState: true,
            });
        },
        [],
    );

    useEffect(() => {
        if (!drag) {
return;
}

        function onMove(e: PointerEvent) {
            const rect = canvasRef.current?.getBoundingClientRect();

            if (!rect || !drag) {
return;
}

            const pctX = ((e.clientX - rect.left) / rect.width) * 100;
            const pctY = ((e.clientY - rect.top) / rect.height) * 100;

            if (drag.kind === 'move-table') {
                setTablePos((p) => ({ ...p, [drag.id]: { x: clamp(pctX, 4, 96), y: clamp(pctY, 5, 95) } }));
            } else if (drag.kind === 'move-element') {
                setElemPos((p) => ({ ...p, [drag.id]: { x: clamp(pctX, 2, 98), y: clamp(pctY, 2, 98) } }));
            } else {
                const dxPct = ((e.clientX - drag.startX) / rect.width) * 100;
                const dyPct = ((e.clientY - drag.startY) / rect.height) * 100;
                setElemSize((s) => ({
                    ...s,
                    [drag.id]: {
                        width: clamp(drag.startW + dxPct * 2, 6, 100),
                        height: clamp(drag.startH + dyPct * 2, 6, 100),
                    },
                }));
            }
        }

        function onUp() {
            if (!drag) {
return;
}

            if (drag.kind === 'move-table') {
                const pos = tablePos[drag.id];

                if (pos) {
persistTable(drag.id, pos.x, pos.y);
}
            } else if (drag.kind === 'move-element') {
                const pos = elemPos[drag.id];

                if (pos) {
persistElement(drag.id, { position_x: Math.round(pos.x), position_y: Math.round(pos.y) });
}
            } else {
                const size = elemSize[drag.id];
                const el = elements.find((e) => e.id === drag.id);

                if (size && el) {
                    const pos = elemPos[drag.id] ?? { x: el.position_x, y: el.position_y };
                    persistElement(drag.id, {
                        position_x: Math.round(pos.x),
                        position_y: Math.round(pos.y),
                        width: Math.round(size.width),
                        height: Math.round(size.height),
                    });
                }
            }

            setDrag(null);
        }

        window.addEventListener('pointermove', onMove);
        window.addEventListener('pointerup', onUp);

        return () => {
            window.removeEventListener('pointermove', onMove);
            window.removeEventListener('pointerup', onUp);
        };
         
    }, [drag, tablePos, elemPos, elemSize, elements, persistTable, persistElement]);

    function assign(guestId: number, tableId: number | null, seatNumber: number | null = null) {
        router.patch(
            '/seating-assign',
            { guest_id: guestId, table_id: tableId, seat_number: seatNumber },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) =>
                    toast.error(errors.seat_number ?? errors.table_id ?? 'Could not seat guest.'),
            },
        );
    }

    // Tap a guest in the rail to pick them up, then tap a seat to place them.
    function tapSeat(table: Table, seatNumber: number, occupied: SeatGuest | undefined) {
        if (selectedGuestId === null) {
            return;
        }

        if (occupied && occupied.id !== selectedGuestId) {
            toast.error('That seat is taken.');
            return;
        }

        assign(selectedGuestId, table.id, seatNumber);
        setSelectedGuestId(null);
    }

    function dropOnSeat(table: Table, seatNumber: number, occupied: SeatGuest | undefined) {
        if (dragGuestId === null) {
return;
}

        if (occupied && occupied.id !== dragGuestId) {
            toast.error('That seat is taken.');
        } else {
            assign(dragGuestId, table.id, seatNumber);
        }

        setDragGuestId(null);
        setDropTarget(null);
    }

    function dropOnUnseated() {
        if (dragGuestId !== null) {
assign(dragGuestId, null);
}

        setDragGuestId(null);
        setDropTarget(null);
    }

    function addElement() {
        const type = options.elementTypes.find((t) => t.value === newElementType);

        if (!type) {
return;
}

        router.post(
            '/seating-elements',
            {
                type: type.value,
                position_x: 50,
                position_y: 50,
                width: type.size[0],
                height: type.size[1],
                rotation: 0,
            },
            { preserveScroll: true, onSuccess: () => toast.success(`${type.label} added.`) },
        );
    }

    function rotateElement(el: FloorElement) {
        const size = elemSizeFor(el);
        const pos = elemPosFor(el);
        persistElement(el.id, {
            position_x: Math.round(pos.x),
            position_y: Math.round(pos.y),
            width: Math.round(size.width),
            height: Math.round(size.height),
            rotation: (el.rotation + 15) % 360,
        });
    }

    function deleteElement(el: FloorElement) {
        router.delete(`/seating-elements/${el.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Element removed.'),
        });
        setSelectedElement(null);
    }

    function saveRoom(width: number, height: number) {
        router.patch(
            '/seating-layout',
            { room_width: clamp(width, 10, 200), room_height: clamp(height, 10, 200) },
            { preserveScroll: true, preserveState: true },
        );
    }

    function openCreate() {
        form.clearErrors();
        form.setDefaults(emptyForm(options));
        form.reset();
        setEditingId(null);
        setSheetOpen(true);
    }

    function openEdit(table: Table) {
        form.clearErrors();
        form.setData({
            name: table.name,
            shape: table.shape,
            capacity: String(table.capacity),
            notes: table.notes ?? '',
        });
        setEditingId(table.id);
        setSheetOpen(true);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        const onSuccess = () => {
            toast.success(editingId ? 'Table updated.' : 'Table added.');
            setSheetOpen(false);
        };

        if (editingId) {
            form.put(`/seating/${editingId}`, { preserveScroll: true, onSuccess });
        } else {
            form.post('/seating', { preserveScroll: true, onSuccess });
        }
    }

    function destroyTable(table: Table) {
        if (!confirm(`Delete ${table.name}? Seated guests will be unseated.`)) {
return;
}

        router.delete(`/seating/${table.id}`, {
            preserveScroll: true,
            onSuccess: () => toast.success('Table deleted.'),
        });
    }

    async function runPrintable(label: string, fn: () => Promise<void>) {
        try {
            await fn();
            toast.success(`${label} ready to print.`);
        } catch (e) {
            toast.error(e instanceof Error ? e.message : 'Could not generate the PDF.');
        }
    }

    return (
        <>
            <Head title="Floor plan" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Seating studio"
                        description="Size the room, place tables and elements, and seat guests chair by chair."
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline">
                                    <Printer className="size-4" /> Printables
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>Print-ready PDFs</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem onSelect={() => runPrintable('Escort cards', () => escortCards(weddingName, guests, tables))}>
                                    Escort cards
                                </DropdownMenuItem>
                                <DropdownMenuItem onSelect={() => runPrintable('Place cards', () => placeCards(weddingName, guests, tables))}>
                                    Place cards
                                </DropdownMenuItem>
                                <DropdownMenuItem onSelect={() => runPrintable('Table numbers', () => tableNumberCards(weddingName, tables))}>
                                    Table numbers
                                </DropdownMenuItem>
                                <DropdownMenuItem onSelect={() => runPrintable('Menu', () => menuCard(weddingName, menu))}>
                                    Menu card
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        <Button variant="outline" onClick={exportFnbSheet} disabled={exportingFnb}>
                            {exportingFnb ? <Spinner className="size-4" /> : <ClipboardList className="size-4" />}
                            {exportingFnb ? 'Exporting…' : 'F&B Sheet'}
                        </Button>
                        <Button variant="outline" onClick={exportFloorPlan} disabled={exporting}>
                            {exporting ? <Spinner className="size-4" /> : <FileDown className="size-4" />}
                            {exporting ? 'Exporting…' : 'Export PDF'}
                        </Button>
                    </div>
                </div>

                <SeatingInfographics stats={stats} />

                {selectedGuestId !== null && (
                    <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#1f5142]/40 bg-[#a8d5c2]/15 px-4 py-2 text-sm">
                        <span>
                            Seating <strong>{guests.find((g) => g.id === selectedGuestId)?.name}</strong> — tap an empty seat to place them.
                        </span>
                        <Button variant="ghost" size="sm" onClick={() => setSelectedGuestId(null)}>
                            Cancel
                        </Button>
                    </div>
                )}

                <div className="flex flex-1 flex-col gap-4 lg:flex-row">
                    {/* LEFT RAIL — guests */}
                    <aside className="flex flex-col lg:w-64">
                        <div className="flex flex-1 flex-col border border-border bg-card">
                            <div className="border-b border-border p-4">
                                <p className="text-xs tracking-[0.2em] text-[#1b4638] uppercase">Guests</p>
                                <div className="relative mt-3">
                                    <Search className="absolute top-1/2 left-0 size-4 -translate-y-1/2 text-muted-foreground" />
                                    <input
                                        value={guestSearch}
                                        onChange={(e) => setGuestSearch(e.target.value)}
                                        placeholder="Find a guest…"
                                        className="w-full border-0 border-b border-border bg-transparent py-2 pl-6 text-sm focus:border-[#1b4638] focus:ring-0"
                                    />
                                </div>
                            </div>
                            <div
                                className={`flex flex-1 flex-col gap-2 overflow-y-auto p-4 ${
                                    dropTarget === 'unseated' ? 'bg-[#a8d5c2]/10' : ''
                                }`}
                                onDragOver={(e) => {
                                    if (dragGuestId !== null) {
                                        e.preventDefault();
                                        setDropTarget('unseated');
                                    }
                                }}
                                onDragLeave={() => setDropTarget((t) => (t === 'unseated' ? null : t))}
                                onDrop={dropOnUnseated}
                            >
                                {filteredUnseated.length === 0 ? (
                                    <p className="py-6 text-center text-xs text-muted-foreground">
                                        {unseated.length === 0 ? 'Everyone has a seat.' : 'No matches.'}
                                    </p>
                                ) : (
                                    filteredUnseated.map((g) => (
                                        <div
                                            key={g.id}
                                            draggable={writable}
                                            onDragStart={() => setDragGuestId(g.id)}
                                            onDragEnd={() => setDragGuestId(null)}
                                            onClick={() =>
                                                writable && setSelectedGuestId((id) => (id === g.id ? null : g.id))
                                            }
                                            className={`flex items-center gap-2 rounded px-3 py-2 text-sm active:cursor-grabbing ${
                                                selectedGuestId === g.id
                                                    ? 'bg-[#a8d5c2]/40 ring-2 ring-[#1f5142]'
                                                    : 'cursor-pointer bg-muted hover:bg-muted/70'
                                            }`}
                                        >
                                            <span
                                                className={`size-2 shrink-0 rounded-full ${
                                                    g.rsvp_status === 'attending'
                                                        ? 'bg-[#1b4638]'
                                                        : g.rsvp_status === 'declined'
                                                          ? 'bg-[#c1b6a8]'
                                                          : 'bg-[#e0d6c9]'
                                                }`}
                                            />
                                            <span className="truncate">{g.name}</span>
                                        </div>
                                    ))
                                )}
                            </div>
                            <div className="flex justify-between border-t border-border p-4 text-[10px] tracking-[0.15em] text-muted-foreground uppercase">
                                <span>Seated: {stats.seated}</span>
                                <span>Total: {stats.seated + stats.unseated}</span>
                            </div>
                        </div>
                    </aside>

                    {/* CENTER — toolbar + canvas */}
                    <div className="flex flex-1 flex-col gap-3">
                        {writable && (
                            <div className="flex flex-wrap items-end gap-4 border border-border bg-card px-4 py-3">
                                <div className="flex items-end gap-2">
                                    <div className="grid gap-1">
                                        <Label className="text-xs">Room width (ft)</Label>
                                        <Input
                                            type="number"
                                            min={10}
                                            max={200}
                                            defaultValue={layout.room_width}
                                            className="w-24"
                                            onBlur={(e) => saveRoom(Number(e.target.value), layout.room_height)}
                                        />
                                    </div>
                                    <span className="pb-2 text-muted-foreground">×</span>
                                    <div className="grid gap-1">
                                        <Label className="text-xs">Length (ft)</Label>
                                        <Input
                                            type="number"
                                            min={10}
                                            max={200}
                                            defaultValue={layout.room_height}
                                            className="w-24"
                                            onBlur={(e) => saveRoom(layout.room_width, Number(e.target.value))}
                                        />
                                    </div>
                                </div>

                                <div className="bg-border h-10 w-px" />

                                <div className="flex items-end gap-2">
                                    <div className="grid gap-1">
                                        <Label className="text-xs">Add element</Label>
                                        <Select value={newElementType} onValueChange={setNewElementType}>
                                            <SelectTrigger className="w-44">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {options.elementTypes.map((t) => (
                                                    <SelectItem key={t.value} value={t.value}>
                                                        {t.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Button variant="outline" onClick={addElement}>
                                        <Plus className="size-4" />
                                        Place
                                    </Button>
                                </div>

                                <div className="bg-border h-10 w-px" />

                                <Button onClick={openCreate} data-test="add-table">
                                    <Plus className="size-4" />
                                    Add table
                                </Button>
                            </div>
                        )}

                        <div
                            ref={canvasRef}
                            onPointerDown={clearSelection}
                            className="relative w-full overflow-hidden rounded-xl border-2 border-[#b9ab97] bg-[#e4e8e0] bg-[radial-gradient(#c9bba6_1.2px,transparent_1.2px)] [background-size:22px_22px]"
                            style={{ aspectRatio: aspect }}
                        >
                            <span className="pointer-events-none absolute top-1 left-1/2 -translate-x-1/2 rounded bg-background/70 px-2 text-[10px] text-muted-foreground">
                                {layout.room_width} ft
                            </span>
                            <span className="pointer-events-none absolute top-1/2 left-1 -translate-y-1/2 rotate-180 rounded bg-background/70 px-2 text-[10px] text-muted-foreground [writing-mode:vertical-rl]">
                                {layout.room_height} ft
                            </span>

                            {tables.length === 0 && elements.length === 0 && (
                                <div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground">
                                    <Armchair className="size-8 opacity-40" />
                                    Add tables and elements to build your floor plan.
                                </div>
                            )}

                            {/* Elements (under tables) */}
                            {elements.map((el) => {
                                const pos = elemPosFor(el);
                                const size = elemSizeFor(el);
                                const Icon = ELEMENT_ICON[el.type] ?? Box;
                                const selected = selectedElement === el.id;

                                return (
                                    <div
                                        key={el.id}
                                        className="absolute"
                                        style={{
                                            left: `${pos.x}%`,
                                            top: `${pos.y}%`,
                                            width: `${size.width}%`,
                                            height: `${size.height}%`,
                                            transform: `translate(-50%, -50%) rotate(${el.rotation}deg)`,
                                        }}
                                        onPointerDown={(e) => {
                                            e.stopPropagation();
                                            setSelectedElement(el.id);
                                            setSelectedTableId(null);
                                        }}
                                    >
                                        <div
                                            className={`flex size-full flex-col items-center justify-center gap-1 rounded-lg border-2 text-center text-xs font-medium transition-colors ${
                                                selected
                                                    ? 'border-[#1b4638] bg-[#a8d5c2]/50 text-[#5b4a1f] ring-2 ring-[#1b4638]/40'
                                                    : 'border-[#9c8f7d] bg-[#e6d8bd] text-[#5b4a1f]'
                                            }`}
                                        >
                                            <Icon className="size-4 shrink-0" />
                                            <span className="px-1 leading-tight">{el.label}</span>
                                        </div>

                                        {writable && (
                                            <span
                                                className="absolute -top-3 left-1/2 -translate-x-1/2 cursor-grab touch-none rounded bg-primary px-1 text-primary-foreground active:cursor-grabbing"
                                                onPointerDown={(e) => {
                                                    e.stopPropagation();
                                                    e.preventDefault();
                                                    setSelectedElement(el.id);
                                                    setSelectedTableId(null);
                                                    setDrag({ kind: 'move-element', id: el.id });
                                                }}
                                                aria-label="Move element"
                                            >
                                                <GripVertical className="size-3.5" />
                                            </span>
                                        )}

                                        {writable && selected && (
                                            <>
                                                <span
                                                    className="absolute -right-3 -bottom-3 cursor-nwse-resize touch-none rounded bg-primary p-0.5 text-primary-foreground"
                                                    onPointerDown={(e) => {
                                                        e.stopPropagation();
                                                        e.preventDefault();
                                                        setDrag({
                                                            kind: 'resize-element',
                                                            id: el.id,
                                                            startX: e.clientX,
                                                            startY: e.clientY,
                                                            startW: size.width,
                                                            startH: size.height,
                                                        });
                                                    }}
                                                    aria-label="Resize element"
                                                >
                                                    <Maximize2 className="size-3" />
                                                </span>
                                                <div className="absolute -top-9 left-1/2 flex -translate-x-1/2 gap-1">
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            rotateElement(el);
                                                        }}
                                                        className="rounded bg-card p-1 shadow ring-1 ring-border hover:text-primary"
                                                        aria-label="Rotate element"
                                                    >
                                                        <RotateCw className="size-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            deleteElement(el);
                                                        }}
                                                        className="rounded bg-card p-1 shadow ring-1 ring-border hover:text-destructive"
                                                        aria-label="Delete element"
                                                    >
                                                        <Trash2 className="size-3.5" />
                                                    </button>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                );
                            })}

                            {/* Tables with chairs */}
                            {tables.map((table) => {
                                const pos = tablePosFor(table);
                                const geo = tableGeometry(table.shape, table.capacity, scale);
                                const occupants = guests.filter((g) => g.table_id === table.id);
                                const seatMap = resolveSeats(occupants, table.capacity);
                                const shapeRadius =
                                    table.shape === 'round'
                                        ? '9999px'
                                        : table.shape === 'square'
                                          ? '0.5rem'
                                          : '0.5rem';

                                return (
                                    <div
                                        key={table.id}
                                        className="absolute"
                                        style={{ left: `${pos.x}%`, top: `${pos.y}%` }}
                                    >
                                        {/* chairs */}
                                        {geo.seats.map((seat) => {
                                            const who = seatMap.get(seat.n);
                                            const target = `seat-${table.id}-${seat.n}`;
                                            const isTarget = dropTarget === target;

                                            return (
                                                <div
                                                    key={seat.n}
                                                    className="absolute -translate-x-1/2 -translate-y-1/2"
                                                    style={{
                                                        left: `calc(50% + ${seat.x}px)`,
                                                        top: `calc(50% + ${seat.y}px)`,
                                                    }}
                                                    onDragOver={(e) => {
                                                        if (dragGuestId !== null) {
                                                            e.preventDefault();
                                                            setDropTarget(target);
                                                        }
                                                    }}
                                                    onDragLeave={() =>
                                                        setDropTarget((t) => (t === target ? null : t))
                                                    }
                                                    onDrop={() => dropOnSeat(table, seat.n, who)}
                                                    onClick={() => tapSeat(table, seat.n, who)}
                                                >
                                                    <div
                                                        draggable={writable && !!who}
                                                        onDragStart={() => who && setDragGuestId(who.id)}
                                                        onDragEnd={() => setDragGuestId(null)}
                                                        title={who ? `Seat ${seat.n}: ${who.name}` : `Seat ${seat.n}`}
                                                        className={`flex items-center justify-center rounded-full border text-[9px] font-semibold transition-all ${
                                                            who
                                                                ? `cursor-grab bg-[#1b4638] text-white ring-2 ring-offset-1 ring-offset-[#e4e8e0] active:cursor-grabbing ${RSVP_RING[who.rsvp_status] ?? 'ring-transparent'}`
                                                                : 'border-2 border-[#9c8f7d] bg-white text-[#7d7468]'
                                                        } ${isTarget ? 'scale-125 ring-2 ring-primary' : ''} ${
                                                            selectedGuestId !== null && !who
                                                                ? 'cursor-pointer ring-2 ring-[#1f5142]/60'
                                                                : ''
                                                        }`}
                                                        style={{ width: geo.chair, height: geo.chair }}
                                                    >
                                                        {who ? initials(who.name) : seat.n}
                                                    </div>
                                                </div>
                                            );
                                        })}

                                        {/* table top */}
                                        <div
                                            className={`absolute top-1/2 left-1/2 flex -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center border-2 bg-white shadow-md ${
                                                selectedTableId === table.id
                                                    ? 'border-[#1b4638] ring-2 ring-[#1b4638]/50'
                                                    : 'border-[#3d3833]'
                                            }`}
                                            style={{
                                                width: geo.tableW,
                                                height: geo.tableH,
                                                borderRadius: shapeRadius,
                                            }}
                                            onPointerDown={(e) => {
                                                e.stopPropagation();
                                                setSelectedTableId(table.id);
                                                setSelectedElement(null);
                                            }}
                                            onDragOver={(e) => {
                                                if (dragGuestId !== null) {
                                                    e.preventDefault();
                                                    setDropTarget(`table-${table.id}`);
                                                }
                                            }}
                                            onDrop={() => {
                                                // drop on table body → first free seat
                                                let free = 1;

                                                while (free <= table.capacity && seatMap.has(free)) {
free++;
}

                                                if (dragGuestId !== null && free <= table.capacity) {
                                                    assign(dragGuestId, table.id, free);
                                                } else if (dragGuestId !== null) {
                                                    toast.error(`${table.name} is full.`);
                                                }

                                                setDragGuestId(null);
                                                setDropTarget(null);
                                            }}
                                        >
                                            {writable && (
                                                <span
                                                    className="absolute -top-2 -left-2 cursor-grab touch-none rounded bg-foreground/80 p-0.5 text-background active:cursor-grabbing"
                                                    onPointerDown={(e) => {
                                                        e.preventDefault();
                                                        e.stopPropagation();
                                                        setDrag({ kind: 'move-table', id: table.id });
                                                    }}
                                                    aria-label="Move table"
                                                >
                                                    <GripVertical className="size-3" />
                                                </span>
                                            )}
                                            <span className="px-1 text-center text-[10px] leading-tight font-semibold">
                                                {table.name}
                                            </span>
                                            <span className="text-[9px] text-muted-foreground">
                                                {occupants.length}/{table.capacity}
                                            </span>
                                            {writable && (
                                                <div className="mt-0.5 flex gap-0.5">
                                                    <button
                                                        type="button"
                                                        onClick={() => openEdit(table)}
                                                        className="text-muted-foreground hover:text-foreground"
                                                        aria-label="Edit table"
                                                    >
                                                        <Pencil className="size-3" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => destroyTable(table)}
                                                        className="text-muted-foreground hover:text-destructive"
                                                        aria-label="Delete table"
                                                    >
                                                        <Trash2 className="size-3" />
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                        <p className="mt-2 text-center text-xs text-muted-foreground">
                            Drag a guest onto a chair to seat them. Drag the grip to move tables and elements.
                        </p>
                    </div>

                    {/* RIGHT RAIL — object details */}
                    <aside className="lg:w-72">
                        <div className="flex h-full flex-col border border-border bg-card p-5">
                            <p className="mb-4 text-xs tracking-[0.2em] text-muted-foreground uppercase">
                                Object details
                            </p>

                            {selectedTable ? (
                                <div className="flex flex-1 flex-col gap-5">
                                    <div>
                                        <h3 className="font-serif text-2xl">{selectedTable.name}</h3>
                                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                            {selectedTable.shape} · {selectedTable.seated}/{selectedTable.capacity} seated
                                        </p>
                                    </div>

                                    <div>
                                        <p className="mb-2 text-xs tracking-widest text-[#1b4638] uppercase">
                                            Seated here
                                        </p>
                                        <div className="flex flex-col gap-1.5">
                                            {guests.filter((g) => g.table_id === selectedTable.id).length === 0 ? (
                                                <p className="text-sm text-muted-foreground">No one seated yet.</p>
                                            ) : (
                                                guests
                                                    .filter((g) => g.table_id === selectedTable.id)
                                                    .map((g) => (
                                                        <div
                                                            key={g.id}
                                                            className="flex items-center justify-between rounded bg-muted px-3 py-1.5 text-sm"
                                                        >
                                                            <span className="truncate">
                                                                {g.seat_number ? `${g.seat_number}. ` : ''}
                                                                {g.name}
                                                            </span>
                                                            {writable && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => assign(g.id, null)}
                                                                    className="text-muted-foreground hover:text-destructive"
                                                                    aria-label={`Unseat ${g.name}`}
                                                                >
                                                                    <X className="size-3.5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    ))
                                            )}
                                        </div>
                                    </div>

                                    {writable && (
                                        <div className="mt-auto flex gap-2">
                                            <Button variant="outline" size="sm" onClick={() => openEdit(selectedTable)}>
                                                <Pencil className="size-4" /> Edit
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => destroyTable(selectedTable)}
                                            >
                                                <Trash2 className="size-4" /> Delete
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ) : selectedElementObj ? (
                                <div className="flex flex-1 flex-col gap-5">
                                    <div>
                                        <h3 className="font-serif text-2xl">{selectedElementObj.label}</h3>
                                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                                            Floor element · {selectedElementObj.rotation}°
                                        </p>
                                    </div>
                                    {writable && (
                                        <div className="mt-auto flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => rotateElement(selectedElementObj)}
                                            >
                                                <RotateCw className="size-4" /> Rotate
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => deleteElement(selectedElementObj)}
                                            >
                                                <Trash2 className="size-4" /> Delete
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="flex flex-1 flex-col items-center justify-center gap-2 text-center text-sm text-muted-foreground">
                                    <Armchair className="size-7 opacity-40" />
                                    Select a table or element to see its details.
                                </div>
                            )}
                        </div>
                    </aside>
                </div>
            </div>

            <Sheet open={sheetOpen} onOpenChange={setSheetOpen}>
                <SheetContent className="overflow-y-auto sm:max-w-md">
                    <SheetHeader>
                        <SheetTitle>{editingId ? 'Edit table' : 'Add table'}</SheetTitle>
                        <SheetDescription>Name the table, pick a shape, and set its capacity.</SheetDescription>
                    </SheetHeader>

                    <form onSubmit={submit} className="flex flex-1 flex-col gap-4 px-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Table name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                autoFocus
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="grid gap-2">
                                <Label>Shape</Label>
                                <Select value={form.data.shape} onValueChange={(v) => form.setData('shape', v)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.shapes.map((o) => (
                                            <SelectItem key={o.value} value={o.value}>
                                                {o.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.shape} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="capacity">Capacity</Label>
                                <Input
                                    id="capacity"
                                    type="number"
                                    min="1"
                                    max="50"
                                    value={form.data.capacity}
                                    onChange={(e) => form.setData('capacity', e.target.value)}
                                />
                                <InputError message={form.errors.capacity} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="notes">Notes</Label>
                            <Textarea
                                id="notes"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        <SheetFooter className="px-0">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing && <Spinner />}
                                {editingId ? 'Save changes' : 'Add table'}
                            </Button>
                        </SheetFooter>
                    </form>
                </SheetContent>
            </Sheet>
        </>
    );
}

SeatingIndex.layout = {
    breadcrumbs: [{ title: 'Floor plan', href: '/seating' }],
};
