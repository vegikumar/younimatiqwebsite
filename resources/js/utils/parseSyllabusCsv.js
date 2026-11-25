/** Parse syllabus CSV text into module row objects (matches admin course create blade). */
export function parseSyllabusCsv(text) {
    let raw = text.replace(/^\uFEFF/, '');
    const firstBreak = raw.search(/\r\n|\n|\r/);
    const firstLine = firstBreak === -1 ? raw : raw.slice(0, firstBreak);
    const delimiter = firstLine.includes('\t') ? '\t' : firstLine.includes(';') ? ';' : ',';

    const records = [];
    let row = [];
    let field = '';
    let inQuotes = false;
    for (let i = 0; i < raw.length; i++) {
        const char = raw[i];
        if (inQuotes) {
            if (char === '"') {
                if (raw[i + 1] === '"') {
                    field += '"';
                    i++;
                } else {
                    inQuotes = false;
                }
            } else {
                field += char;
            }
        } else if (char === '"') {
            inQuotes = true;
        } else if (char === delimiter) {
            row.push(field);
            field = '';
        } else if (char === '\n' || char === '\r') {
            if (char === '\r' && raw[i + 1] === '\n') i++;
            row.push(field);
            field = '';
            records.push(row);
            row = [];
        } else {
            field += char;
        }
    }
    if (field !== '' || row.length) {
        row.push(field);
        records.push(row);
    }

    const cleaned = records.filter((r) => r.some((c) => c.trim() !== '') && !r[0].trim().startsWith('##'));
    if (cleaned.length < 2) return [];

    const headers = cleaned[0].map((h) => h.trim().toLowerCase().replace(/[\s\-\.]+/g, '_'));
    const findIdx = (aliases) => headers.findIndex((h) => aliases.includes(h));

    const titleIdx = findIdx(['class_title', 'class_name', 'module_title', 'topic', 'title', 'lesson_title']);
    const descIdx = findIdx(['class_description', 'module_description', 'description', 'details']);
    const outcomeIdx = findIdx(['class_learning_outcome', 'learning_outcome', 'outcome', 'learning_outcomes']);
    const videoIdx = findIdx(['practical_video_url', 'practical_video', 'demo_video_url', 'demo_video']);

    if (titleIdx === -1) return [];

    const results = [];
    for (let i = 1; i < cleaned.length; i++) {
        const cols = cleaned[i];
        const title = (cols[titleIdx] || '').trim();
        if (!title) continue;
        results.push({
            id: Date.now() + i,
            class_name: title,
            description: descIdx !== -1 ? (cols[descIdx] || '').trim() : '',
            outcome: outcomeIdx !== -1 ? (cols[outcomeIdx] || '').trim() : '',
            practical_video_url: videoIdx !== -1 ? (cols[videoIdx] || '').trim() : '',
        });
    }
    return results;
}
