const fs = require('fs');
const path = require('path');

const DATA_DIR = path.join(__dirname, '../frontend/DViz/data');
const OUTPUT_FILE = path.join(__dirname, '../frontend/DViz/js/dviz_data.js');

// Helper to clean display names
function cleanDisplayName(filename) {
    // Remove extension
    let name = path.parse(filename).name;

    // Remove prefix (s, s1, m, m2, i, i1, s), m), etc.) followed by separator
    // Pattern: ^[a-z]+[0-9]*[\_\-\)\.\s]+
    name = name.replace(/^[a-z]+[0-9]*[\_\-\)\.\s]+/i, '');

    // Remove remaining underscores/dashes
    name = name.replace(/[\_\-]/g, ' ');

    // Trim
    name = name.trim();

    // Title Case
    return name.replace(/\w\S*/g, (txt) => txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase());
}

// Helper to identify category from filename
function getCategory(filename) {
    const lower = filename.toLowerCase();
    if (lower.startsWith('s')) return 'short_questions';
    if (lower.startsWith('m')) return 'mcqs';
    if (lower.startsWith('i')) return 'summary'; // 'i' for ideas/images usually, but PDF i* is visual summary per spec
    // Wait, spec 7.1 says:
    // s* -> Short Questions
    // m* -> MCQs
    // i* -> Visual Summary (PDF)

    // Spec 6 says:
    // Videos folder -> Videos
    // Images folder -> Ideas
    return 'unknown';
}

function scanDirectory() {
    console.log(`Scanning ${DATA_DIR}...`);

    if (!fs.existsSync(DATA_DIR)) {
        console.error(`Data directory not found at ${DATA_DIR}`);
        return;
    }

    const index = {
        levels: []
    };

    // 1. Scan Levels (part*)
    const parts = fs.readdirSync(DATA_DIR).filter(f => f.startsWith('part') && fs.statSync(path.join(DATA_DIR, f)).isDirectory());

    // Sort parts naturally (part1, part2, part10)
    parts.sort((a, b) => {
        const numA = parseInt(a.replace('part', ''));
        const numB = parseInt(b.replace('part', ''));
        return numA - numB;
    });

    parts.forEach(partDir => {
        const levelId = partDir.replace('part', '');
        const levelPath = path.join(DATA_DIR, partDir);

        const levelObj = {
            id: levelId,
            dir: partDir,
            label: `Level ${levelId}`,
            visuals: []
        };

        // 2. Scan Visuals (chapter*)
        const chapters = fs.readdirSync(levelPath).filter(f => fs.statSync(path.join(levelPath, f)).isDirectory());

        // Sort chapters naturally
        chapters.sort((a, b) => { // Assuming chapter1, chapter2...
            const numA = parseInt(a.match(/\d+/)?.[0] || 0);
            const numB = parseInt(b.match(/\d+/)?.[0] || 0);
            return numA - numB;
        });

        chapters.forEach(chapDir => {
            const chapPath = path.join(levelPath, chapDir);

            // Try to derive a nice name if possible, or just use "Chapter X" if no metadata file
            // Spec says "Real chapter names (derived from data/index)". 
            // Assuming for now we map chapter folder name or look for a file? 
            // Spec 5: "Title: Real chapter name". 
            // Usually this means we need a way to know "chapter1" is "Introduction".
            // Since we don't have a manifest, we might default to "Chapter X" OR look for the first 'i*.png' inside images/ or slides/?
            // Actually, spec 7.2: "Display Name Extraction Formula... Applies to PDFs, Images, Videos". 
            // Maybe the chapter name comes from the content filenames? 
            // "s_Software_Development.pdf" -> "Software Development". 
            // YES. If inside chapter1/slides/s_Software_Development.pdf exists, chapter name is likely "Software Development".

            let displayName = `Chapter ${chapDir.replace('chapter', '')}`; // Fallback

            const visualObj = {
                id: chapDir.replace('chapter', ''),
                dir: chapDir,
                title: displayName, // Will update below
                assets: {
                    presentations: [],
                    videos: [],
                    ideas: [],
                    summary: [] // i* pdfs
                }
            };

            // Scan subfolders
            const subfolders = ['slides', 'images', 'videos'];

            subfolders.forEach(sub => {
                const subPath = path.join(chapPath, sub);
                if (fs.existsSync(subPath)) {
                    const files = fs.readdirSync(subPath).filter(f => !f.startsWith('.'));

                    files.forEach(file => {
                        const relativePath = `data/${partDir}/${chapDir}/${sub}/${file}`;
                        const display = cleanDisplayName(file);

                        // Heuristic to update Chapter Title from the first meaningful file found
                        if (visualObj.title.startsWith('Chapter ')) {
                            // Prefer Short Question or Summary title, but only if length > 3 to avoid "S1", "I1"
                            if ((file.toLowerCase().startsWith('s') || file.toLowerCase().startsWith('i')) && display.length > 3) {
                                visualObj.title = display;
                            }
                        }

                        // Categorize
                        // Slides folder contains: s*.pdf, m*.pdf, i*.pdf
                        if (sub === 'slides') {
                            if (file.toLowerCase().startsWith('s')) {
                                visualObj.assets.presentations.push({ type: 'short', title: display, path: relativePath });
                            } else if (file.toLowerCase().startsWith('m')) {
                                visualObj.assets.presentations.push({ type: 'mcq', title: display, path: relativePath });
                            } else if (file.toLowerCase().startsWith('i')) {
                                // i* pdf is Visual Summary
                                visualObj.assets.summary.push({ title: display, path: relativePath });
                            }
                        }
                        // Images folder -> Ideas
                        else if (sub === 'images') {
                            visualObj.assets.ideas.push({ title: display, path: relativePath });
                        }
                        // Videos folder -> Videos
                        else if (sub === 'videos') {
                            visualObj.assets.videos.push({ title: display, path: relativePath });
                        }
                    });
                }
            });

            levelObj.visuals.push(visualObj);
        });

        index.levels.push(levelObj);
    });

    // Write Index
    // Write Index
    fs.writeFileSync(OUTPUT_FILE, 'window.DVIZ_DATA = ' + JSON.stringify(index, null, 2) + ';');
    console.log(`Generated 'js/dviz_data.js' successfully with ${index.levels.length} levels.`);
}

scanDirectory();
