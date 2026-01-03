document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('bsc-recipe-builder-container');
    if (!container) return;

    // Configuration for Fields
    const fieldConfig = {
        mash: { dur: true, temp: true },
        sparge: { dur: true, temp: true },
        boil: { dur: true, temp: false },
        whirlpool: { dur: true, temp: true },
        chill: { dur: false, temp: true },
        ferment: { dur: true, temp: true },
        dryhop: { dur: true, temp: false },
        aging: { dur: true, temp: true },
        package: { dur: false, temp: false },
        carb: { dur: true, temp: true },
        prep: { dur: false, temp: false }
    };

    // Malt List Data
    const maltList = {
        "Malts de Base": [
            "Orge", "Pilsner / Pilsen", "Pale Ale", "Lager", "Vienna", "Munich Light", "Munich Dark",
            "Mild Malt", "Maris Otter", "Golden Promise", "Heritage Malt", "Low Color Pale", "High Color Pale",
            "Malt de blé", "Malt de blé clair", "Malt de blé foncé", "Malt de seigle", "Malt d’épeautre",
            "Malt d’avoine", "Malt de triticale"
        ],
        "Malts Caramel / Crystal": [
            "Caramel / Crystal", "Carapils / Dextrin Malt", "Crystal 10", "Crystal 20", "Crystal 30",
            "Crystal 40", "Crystal 60", "Crystal 80", "Crystal 120", "Caramel Light", "Caramel Medium",
            "Caramel Dark", "CaraAmber", "CaraRed", "CaraMunich I", "CaraMunich II", "CaraMunich III",
            "CaraGold", "CaraHell", "CaraAroma", "Carawheat", "CaraRye", "CaraOat"
        ],
        "Malts Torréfiés / Foncés": [
            "Chocolate Malt", "Dark Chocolate Malt", "Black Malt", "Black Patent", "Roasted Barley",
            "Brown Malt", "Amber Malt", "Chocolate Wheat", "Roasted Wheat", "Chocolate Rye"
        ],
        "Malts Spéciaux / Techniques": [
            "Acidulated Malt", "Melanoidin Malt", "Biscuit Malt", "Victory Malt", "Honey Malt",
            "Special B", "Smoked Malt", "Peated Malt", "Diastatic Malt", "Malt enzymatique"
        ],
        "Malts Fumés": [
            "Rauchmalz (hêtre)", "Smoked Beechwood", "Smoked Oak", "Smoked Cherrywood", "Smoked Peat", "Smoked Wheat"
        ],
        "Sans Gluten / Alternatifs": [
            "Malt de sorgho", "Malt de millet", "Malt de riz", "Malt de maïs", "Malt de quinoa", "Malt de sarrasin"
        ],
        "Céréales Non Maltées": [
            "Flocons d’avoine", "Flocons d’orge", "Flocons de blé", "Flocons de seigle", "Riz cru",
            "Maïs", "Sucre de canne", "Sucre candi clair / foncé", "Miel", "Lactose"
        ]
    };

    // Hop List Data
    const hopList = {
        "Amérisants": [
            "Magnum", "Warrior", "Columbus (CTZ)", "Tomahawk", "Zeus", "Chinook", "Target", "Galena",
            "Horizon", "Nugget", "Bravo", "Apollo", "Northern Brewer", "Brewer’s Gold", "Pride of Ringwood", "Herkules", "Pahto"
        ],
        "Aromatiques (Europe)": [
            "Hallertau Mittelfrüh", "Hallertau Tradition", "Tettnang", "Spalt", "Spalter Select", "Hersbrucker", "Perle",
            "Saaz", "Strisselspalt", "Aramis", "Triskel", "Bouclier",
            "East Kent Goldings (EKG)", "Fuggle", "Challenger", "Bramling Cross", "Progress", "First Gold"
        ],
        "Modernes (USA/Pacific)": [
            "Cascade", "Centennial", "Citra", "Amarillo", "Simcoe", "Mosaic", "Idaho 7", "El Dorado", "Azacca", "Sabro", "Cashmere", "Crystal", "Liberty", "Mount Hood",
            "Galaxy", "Vic Secret", "Enigma", "Ella", "Motueka", "Nelson Sauvin", "Riwaka", "Wai-iti", "Pacific Jade", "Pacifica"
        ],
        "Expérimentaux / Nouveaux": [
            "HBC 472", "HBC 586 (Krush)", "HBC 630", "HBC 638", "HBC 682", "HBC 692", "HBC 1019", "HBC 431",
            "YCH 301 (Popcorn)", "Sabro Cryo", "Citra Cryo", "Mosaic Cryo"
        ],
        "Européens Modernes": [
            "Mandarina Bavaria", "Hallertau Blanc", "Huell Melon", "Ariana", "Callista", "Saphir", "Polaris"
        ],
        "Spéciaux": [
            "Styrian Goldings", "Lubelski", "Loral", "Sorachi Ace", "Sterling"
        ]
    };

    const hopForms = ["Pellets T90", "Pellets T45", "Cônes entiers", "Cryo Hops", "Hop Extract"];

    const aromaTags = [
        "Agrumes", "Fruits Tropicaux", "Fruits à noyau", "Résine / Pin", "Floral",
        "Épicé", "Herbacé", "Terreux", "Vin blanc", "Noix de coco", "Fruits rouges"
    ];

    // Yeast Data
    const yeastList = {
        "Levures Ale": [
            "US-05 (Chico)", "California Ale", "American West Coast Ale", "BRY-97", "Nottingham Ale", "SafAle S-04",
            "London Ale", "London Ale III", "British Ale", "Irish Ale",
            "Belgian Abbey", "Belgian Strong Ale", "Belgian Ardennes", "Trappist Ale", "Belgian Saison", "Belgian Witbier", "Belgian Blonde", "Belgian Dark Ale",
            "Bière de Garde", "Biere de Mars", "Farmhouse Ale"
        ],
        "Levures Lager": [
            "German Lager", "Bavarian Lager", "Munich Lager", "Oktoberfest Lager", "Märzen Lager", "Bock Lager", "Doppelbock Lager", "Schwarzbier Lager", "Helles Lager",
            "Bohemian Lager", "Czech Pilsner", "Pilsner Lager", "Vienna Lager", "Export Lager",
            "American Lager", "American Light Lager", "Pre-Prohibition Lager"
        ],
        "Levures Saison & Farmhouse": [
            "Saison Dupont-style", "French Saison", "Rustic Ale"
        ],
        "Levures Blé / Wheat": [
            "German Wheat", "Bavarian Weizen", "Hefeweizen", "Dunkelweizen", "Weizenbock",
            "Witbier", "Belgian Wheat", "White Ale"
        ],
        "Levures Acides & Sauvages": [
            "Brettanomyces bruxellensis", "Brettanomyces lambicus", "Brettanomyces claussenii", "Brettanomyces anomalus",
            "Lactobacillus", "Pediococcus", "Oenococcus",
            "Lambic Blend", "Gueuze Blend", "Berliner Weisse Blend"
        ],
        "Levures Kveik": [
            "Voss Kveik", "Hornindal Kveik", "Lutra Kveik", "Ebbegarden Kveik", "Skare Kveik"
        ],
        "Levures Hybrides": [
            "Kölsch", "Altbier", "California Common (Steam)", "Cream Ale"
        ],
        "Levures Spécifiques": [
            "Rice Lager Yeast", "Cider Yeast", "Mead Yeast", "Wine Yeast"
        ]
    };

    const yeastForms = ["Sèche", "Liquide", "Culture maison", "Réensemencement"];
    const yeastBrands = ["Fermentis", "Lallemand", "Mangrove Jack’s", "White Labs", "Wyeast", "Omega Yeast", "Imperial Yeast"];

    // Initial Data
    let steps = [];
    if (typeof bscRecipeData !== 'undefined' && bscRecipeData.steps) {
        steps = bscRecipeData.steps;
    } else {
        // Default Step
        steps = [{
            id: Date.now(),
            title: 'Empâtage',
            type: 'mash',
            duration: '',
            temp: '',
            comment: '',
            items: []
        }];
    }

    // Render Function
    function render() {
        container.innerHTML = '';

        steps.forEach((step, index) => {
            // Auto-detect type if missing or empty, based on title
            if (!step.type) {
                step.type = detectType(step.title);
            }

            const config = fieldConfig[step.type] || fieldConfig['prep'];
            const showMetrics = config.dur || config.temp;

            const stepHtml = `
                <div class="bsc-step-wrapper" data-index="${index}">
                    <div class="bsc-timeline-column">
                        <div class="bsc-timeline-line"></div>
                        <div class="bsc-timeline-icon bsc-bg-${step.type}">${getIcon(step.type)}</div>
                    </div>

                    <div class="bsc-step-card bsc-type-${step.type}">
                        <button type="button" class="bsc-remove-step" onclick="bscRemoveStep(${index})">&times;</button>

                        <div class="bsc-step-header">
                            <div class="bsc-header-row">
                                <select class="bsc-step-type-select" onchange="bscUpdateStep(${index}, 'type', this.value)">
                                    <option value="prep" ${step.type === 'prep' ? 'selected' : ''}>Préparation</option>
                                    <option value="mash" ${step.type === 'mash' ? 'selected' : ''}>Empâtage</option>
                                    <option value="sparge" ${step.type === 'sparge' ? 'selected' : ''}>Rinçage</option>
                                    <option value="boil" ${step.type === 'boil' ? 'selected' : ''}>Ébullition</option>
                                    <option value="whirlpool" ${step.type === 'whirlpool' ? 'selected' : ''}>Whirlpool</option>
                                    <option value="chill" ${step.type === 'chill' ? 'selected' : ''}>Refroidissement</option>
                                    <option value="ferment" ${step.type === 'ferment' ? 'selected' : ''}>Fermentation</option>
                                    <option value="dryhop" ${step.type === 'dryhop' ? 'selected' : ''}>Dry Hop</option>
                                    <option value="aging" ${step.type === 'aging' ? 'selected' : ''}>Garde</option>
                                    <option value="package" ${step.type === 'package' ? 'selected' : ''}>Conditionnement</option>
                                    <option value="carb" ${step.type === 'carb' ? 'selected' : ''}>Carbonatation</option>
                                </select>
                                <input type="text" class="bsc-step-title-input" value="${esc(step.title)}" placeholder="Titre de l'étape" onchange="bscUpdateStep(${index}, 'title', this.value)">
                            </div>

                            ${showMetrics ? `
                            <div class="bsc-step-metrics">
                                ${config.dur ? `
                                <div class="bsc-metric">
                                    <span class="bsc-metric-label">Durée</span>
                                    <div class="bsc-metric-input-wrapper">
                                        <input type="number" class="bsc-metric-input" value="${esc(step.duration)}" onchange="bscUpdateStep(${index}, 'duration', this.value)">
                                        <span class="bsc-metric-unit">min</span>
                                    </div>
                                </div>` : ''}

                                ${config.temp ? `
                                <div class="bsc-metric">
                                    <span class="bsc-metric-label">Temp</span>
                                    <div class="bsc-metric-input-wrapper">
                                        <input type="text" class="bsc-metric-input" value="${esc(step.temp)}" onchange="bscUpdateStep(${index}, 'temp', this.value)">
                                        <span class="bsc-metric-unit">°C</span>
                                    </div>
                                </div>` : ''}
                            </div>` : ''}
                        </div>

                        <div class="bsc-step-extra">
                            <textarea class="bsc-step-comment" placeholder="Commentaires / Instructions..." onchange="bscUpdateStep(${index}, 'comment', this.value)">${esc(step.comment)}</textarea>
                            <div class="bsc-step-image-upload">
                                <label class="bsc-file-label">Photo de l'étape 📸</label>
                                <input type="file" name="bsc_step_image_${step.id}" accept="image/*">
                                ${step.image_url ? `<div class="bsc-img-preview"><img src="${step.image_url}" style="max-height:50px;"></div>` : ''}
                            </div>
                        </div>

                        <div class="bsc-items-container">
                            ${step.items.map((item, itemIndex) => {
                                const isMalt = item.type === 'Malt';
                                const isHop = item.type === 'Hop';
                                const isYeast = item.type === 'Yeast';
                                let wrapperClass = '';
                                if(isMalt) wrapperClass = 'bsc-item-malt-wrapper';
                                if(isHop) wrapperClass = 'bsc-item-hop-wrapper';
                                if(isYeast) wrapperClass = 'bsc-item-yeast-wrapper';

                                return `
                                <div class="bsc-item-wrapper ${wrapperClass}">
                                    <div class="bsc-item-row">
                                        <div class="bsc-item-icon">${getIngredientIcon(item.type)}</div>
                                        <select class="bsc-item-select" onchange="bscUpdateItem(${index}, ${itemIndex}, 'type', this.value)">
                                            <option value="Malt" ${item.type === 'Malt' ? 'selected' : ''}>Malt</option>
                                            <option value="Hop" ${item.type === 'Hop' ? 'selected' : ''}>Houblon</option>
                                            <option value="Yeast" ${item.type === 'Yeast' ? 'selected' : ''}>Levure</option>
                                            <option value="Adjunct" ${item.type === 'Adjunct' ? 'selected' : ''}>Autre</option>
                                            <option value="Technique" ${item.type === 'Technique' ? 'selected' : ''}>Technique</option>
                                            <option value="Equipment" ${item.type === 'Equipment' ? 'selected' : ''}>Matériel</option>
                                        </select>

                                        ${isMalt ? renderMaltSelect(item.name, index, itemIndex) :
                                          isHop ? renderHopSelect(item.name, index, itemIndex) :
                                          isYeast ? renderYeastSelect(item.name, index, itemIndex) : `
                                        <input type="text" class="bsc-item-input" value="${esc(item.name)}" placeholder="Nom" onchange="bscUpdateItem(${index}, ${itemIndex}, 'name', this.value)">
                                        `}

                                        <input type="text" class="bsc-item-input" value="${esc(item.qty)}" placeholder="Qté" onchange="bscUpdateItem(${index}, ${itemIndex}, 'qty', this.value)">
                                        <button type="button" class="bsc-btn-icon" onclick="bscRemoveItem(${index}, ${itemIndex})">&times;</button>
                                    </div>

                                    ${isMalt ? `
                                    <div class="bsc-item-details">
                                        <input type="text" class="bsc-detail-input" placeholder="Fournisseur" value="${esc(item.supplier)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'supplier', this.value)">
                                        <input type="number" class="bsc-detail-input" placeholder="EBC" value="${esc(item.ebc)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'ebc', this.value)">
                                        <input type="number" class="bsc-detail-input" placeholder="Rendement %" value="${esc(item.yield)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'yield', this.value)">
                                    </div>
                                    ` : ''}

                                    ${isHop ? `
                                    <div class="bsc-item-details bsc-hop-details">
                                        <input type="number" class="bsc-detail-input" placeholder="AA %" value="${esc(item.alpha)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'alpha', this.value)">
                                        <select class="bsc-detail-input" onchange="bscUpdateItem(${index}, ${itemIndex}, 'form', this.value)">
                                            <option value="">Forme</option>
                                            ${hopForms.map(f => `<option value="${f}" ${item.form === f ? 'selected' : ''}>${f}</option>`).join('')}
                                        </select>
                                        <input type="text" class="bsc-detail-input" placeholder="Année" value="${esc(item.year)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'year', this.value)">
                                        <input type="text" class="bsc-detail-input" placeholder="Origine" value="${esc(item.origin)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'origin', this.value)">
                                    </div>
                                    <div class="bsc-item-details bsc-hop-tags">
                                        <span class="bsc-tags-label">Profil: </span>
                                        ${renderAromaTags(item.aromas, index, itemIndex)}
                                    </div>
                                    ` : ''}

                                    ${isYeast ? `
                                    <div class="bsc-item-details bsc-yeast-details">
                                        <select class="bsc-detail-input" onchange="bscUpdateItem(${index}, ${itemIndex}, 'brand', this.value)">
                                            <option value="">Marque</option>
                                            ${yeastBrands.map(b => `<option value="${b}" ${item.brand === b ? 'selected' : ''}>${b}</option>`).join('')}
                                        </select>
                                        <select class="bsc-detail-input" onchange="bscUpdateItem(${index}, ${itemIndex}, 'form', this.value)">
                                            <option value="">Format</option>
                                            ${yeastForms.map(f => `<option value="${f}" ${item.form === f ? 'selected' : ''}>${f}</option>`).join('')}
                                        </select>
                                        <input type="text" class="bsc-detail-input" placeholder="Atténuation %" value="${esc(item.attenuation)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'attenuation', this.value)">
                                        <input type="text" class="bsc-detail-input" placeholder="Temp. Optimale" value="${esc(item.temp_opt)}" onchange="bscUpdateItem(${index}, ${itemIndex}, 'temp_opt', this.value)">
                                    </div>
                                    ` : ''}

                                </div>
                            `;}).join('')}
                            <button type="button" class="bsc-btn-add-item" onclick="bscAddItem(${index})">+ Ajouter un élément</button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', stepHtml);
        });

        const addBtn = `<div class="bsc-add-step-wrapper"><button type="button" class="bsc-btn-add-step" onclick="bscAddStep()">+ Ajouter une étape</button></div>`;
        container.insertAdjacentHTML('beforeend', addBtn);

        updateHiddenInputs();
    }

    // Render Malt Select Helper
    function renderMaltSelect(currentVal, stepIndex, itemIndex) {
        let options = `<option value="">-- Choisir un Malt --</option>`;
        for (const [category, items] of Object.entries(maltList)) {
            options += `<optgroup label="${category}">`;
            items.forEach(malt => {
                const selected = malt === currentVal ? 'selected' : '';
                options += `<option value="${malt}" ${selected}>${malt}</option>`;
            });
            options += `</optgroup>`;
        }
        return `<select class="bsc-item-input bsc-malt-select" onchange="bscUpdateItem(${stepIndex}, ${itemIndex}, 'name', this.value)">${options}</select>`;
    }

    // Render Hop Select Helper
    function renderHopSelect(currentVal, stepIndex, itemIndex) {
        let options = `<option value="">-- Choisir un Houblon --</option>`;
        for (const [category, items] of Object.entries(hopList)) {
            options += `<optgroup label="${category}">`;
            items.forEach(hop => {
                const selected = hop === currentVal ? 'selected' : '';
                options += `<option value="${hop}" ${selected}>${hop}</option>`;
            });
            options += `</optgroup>`;
        }
        return `<select class="bsc-item-input bsc-hop-select" onchange="bscUpdateItem(${stepIndex}, ${itemIndex}, 'name', this.value)">${options}</select>`;
    }

    // Render Yeast Select Helper
    function renderYeastSelect(currentVal, stepIndex, itemIndex) {
        let options = `<option value="">-- Choisir une Levure --</option>`;
        for (const [category, items] of Object.entries(yeastList)) {
            options += `<optgroup label="${category}">`;
            items.forEach(yeast => {
                const selected = yeast === currentVal ? 'selected' : '';
                options += `<option value="${yeast}" ${selected}>${yeast}</option>`;
            });
            options += `</optgroup>`;
        }
        return `<select class="bsc-item-input bsc-yeast-select" onchange="bscUpdateItem(${stepIndex}, ${itemIndex}, 'name', this.value)">${options}</select>`;
    }

    function renderAromaTags(currentAromas, stepIndex, itemIndex) {
        // currentAromas is array of strings
        const selected = Array.isArray(currentAromas) ? currentAromas : [];
        let html = '';
        aromaTags.forEach(tag => {
            const isChecked = selected.includes(tag) ? 'checked' : '';
            html += `
                <label class="bsc-tag-checkbox">
                    <input type="checkbox" value="${tag}" ${isChecked} onchange="bscToggleAroma(${stepIndex}, ${itemIndex}, this.value)">
                    ${tag}
                </label>
            `;
        });
        return `<div class="bsc-tags-container">${html}</div>`;
    }

    // Helpers
    function esc(str) {
        if (!str) return '';
        return str.replace(/"/g, '&quot;');
    }

    function detectType(title) {
        if (!title) return 'prep';
        const t = title.toLowerCase();
        if (t.includes('mash') || t.includes('empâtage') || t.includes('empatage')) return 'mash';
        if (t.includes('sparge') || t.includes('rincage') || t.includes('rinçage')) return 'sparge';
        if (t.includes('boil') || t.includes('ebullition') || t.includes('ébullition')) return 'boil';
        if (t.includes('whirlpool')) return 'whirlpool';
        if (t.includes('chill') || t.includes('refroidissement')) return 'chill';
        if (t.includes('ferment')) return 'ferment';
        if (t.includes('dry') && t.includes('hop')) return 'dryhop';
        if (t.includes('aging') || t.includes('garde')) return 'aging';
        if (t.includes('package') || t.includes('conditionnement') || t.includes('bouteille') || t.includes('fût')) return 'package';
        if (t.includes('carb') || t.includes('sucre')) return 'carb';
        return 'prep';
    }

    function getIcon(type) {
        switch(type) {
            case 'mash': return '🌾';
            case 'sparge': return '🚿';
            case 'boil': return '🔥';
            case 'whirlpool': return '🌀';
            case 'chill': return '❄️';
            case 'ferment': return '🦠';
            case 'dryhop': return '🌿';
            case 'aging': return '🕰️';
            case 'package': return '📦';
            case 'carb': return '🫧';
            case 'prep': default: return '⚙️';
        }
    }

    function getIngredientIcon(type) {
        switch(type) {
            case 'Malt': return '🌾';
            case 'Hop': return '🌿';
            case 'Yeast': return '🦠';
            case 'Adjunct': return '🍬';
            case 'Technique': return '🛠️';
            case 'Equipment': return '⚙️';
            default: return '🔹';
        }
    }

    // Global Handlers
    window.bscAddStep = function() {
        steps.push({ id: Date.now(), title: '', type: 'prep', duration: '', temp: '', comment: '', items: [] });
        render();
    };

    window.bscRemoveStep = function(index) {
        if (confirm('Supprimer cette étape ?')) {
            steps.splice(index, 1);
            render();
        }
    };

    window.bscUpdateStep = function(index, key, value) {
        steps[index][key] = value;
        if (key === 'title' && steps[index].type === 'prep' && value) {
             const detected = detectType(value);
             if (detected !== 'prep') steps[index].type = detected;
        }
        render();
    };

    window.bscAddItem = function(stepIndex) {
        // Default new item
        steps[stepIndex].items.push({ type: 'Malt', name: '', qty: '', ebc: '', supplier: '', yield: '' });
        render();
    };

    window.bscRemoveItem = function(stepIndex, itemIndex) {
        steps[stepIndex].items.splice(itemIndex, 1);
        render();
    };

    window.bscUpdateItem = function(stepIndex, itemIndex, key, value) {
        steps[stepIndex].items[itemIndex][key] = value;

        // Init fields for Hop/Yeast
        if (key === 'type' && value === 'Hop') {
             if (!steps[stepIndex].items[itemIndex].aromas) steps[stepIndex].items[itemIndex].aromas = [];
             render();
             return;
        }
        if (key === 'type' && value === 'Yeast') {
             render();
             return;
        }

        if (key === 'type') {
            render();
        } else {
            updateHiddenInputs();
        }
    };

    window.bscToggleAroma = function(stepIndex, itemIndex, tag) {
        let item = steps[stepIndex].items[itemIndex];
        if (!item.aromas) item.aromas = [];

        const idx = item.aromas.indexOf(tag);
        if (idx > -1) {
            item.aromas.splice(idx, 1);
        } else {
            item.aromas.push(tag);
        }
        updateHiddenInputs();
    };

    function updateHiddenInputs() {
        document.getElementById('bsc_recipe_steps').value = JSON.stringify(steps);

        let malts = [];
        let hops = [];
        let yeasts = [];
        let mash = [];
        let ingredients = [];
        let equipment = [];

        steps.forEach(step => {
            if (step.title || step.duration) {
                mash.push(`${step.title}: ${step.duration}min @ ${step.temp}°C`);
            }
            step.items.forEach(item => {
                let line = `${item.name} (${item.qty})`;

                if (item.type === 'Malt') {
                    const extras = [];
                    if (item.supplier) extras.push(item.supplier);
                    if (item.ebc) extras.push(`${item.ebc} EBC`);
                    if (extras.length > 0) line += ` [${extras.join(', ')}]`;
                    malts.push(line);
                }

                if (item.type === 'Hop') {
                    const extras = [];
                    if (item.alpha) extras.push(`${item.alpha}% AA`);
                    if (item.form) extras.push(item.form);
                    if (item.origin) extras.push(item.origin);
                    if (item.aromas && item.aromas.length > 0) extras.push(`Profil: ${item.aromas.join(', ')}`);

                    if (extras.length > 0) line += ` [${extras.join(' | ')}]`;
                    hops.push(line);
                }

                if (item.type === 'Yeast') {
                    const extras = [];
                    if (item.brand) extras.push(item.brand);
                    if (item.form) extras.push(item.form);
                    if (item.attenuation) extras.push(`Atténuation: ${item.attenuation}%`);
                    if (item.temp_opt) extras.push(`Temp: ${item.temp_opt}`);

                    if (extras.length > 0) line += ` [${extras.join(' | ')}]`;
                    yeasts.push(line);
                }

                if (item.type === 'Equipment') equipment.push(line);

                if (['Malt', 'Hop', 'Yeast', 'Adjunct'].includes(item.type)) {
                    ingredients.push(`${item.type}: ${line}`);
                }
            });
        });

        if(document.getElementById('bsc_malts')) document.getElementById('bsc_malts').value = malts.join(', ');
        if(document.getElementById('bsc_hops')) document.getElementById('bsc_hops').value = hops.join(', ');
        if(document.getElementById('bsc_mash_schedule')) document.getElementById('bsc_mash_schedule').value = mash.join('\n');
        if(document.getElementById('bsc_ingredients_list')) document.getElementById('bsc_ingredients_list').value = ingredients.join('\n');
        if(document.getElementById('bsc_equipment')) document.getElementById('bsc_equipment').value = equipment.join(', ');
    }

    render();
});
