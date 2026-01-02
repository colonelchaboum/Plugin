document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('bsc-recipe-builder-container');
    if (!container) return;

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

            const stepHtml = `
                <div class="bsc-step-card bsc-type-${step.type}" data-index="${index}">
                    <div class="bsc-timeline-connector"></div>
                    <div class="bsc-step-icon">${getIcon(step.type)}</div>

                    <button type="button" class="bsc-remove-step" onclick="bscRemoveStep(${index})">&times;</button>

                    <div class="bsc-step-header">
                        <div class="bsc-header-row">
                            <select class="bsc-step-type-select" onchange="bscUpdateStep(${index}, 'type', this.value)">
                                <option value="prep" ${step.type === 'prep' ? 'selected' : ''}>Préparation</option>
                                <option value="mash" ${step.type === 'mash' ? 'selected' : ''}>Empâtage</option>
                                <option value="boil" ${step.type === 'boil' ? 'selected' : ''}>Ébullition</option>
                                <option value="ferment" ${step.type === 'ferment' ? 'selected' : ''}>Fermentation</option>
                                <option value="package" ${step.type === 'package' ? 'selected' : ''}>Conditionnement</option>
                            </select>
                            <input type="text" class="bsc-step-title-input" value="${esc(step.title)}" placeholder="Titre de l'étape" onchange="bscUpdateStep(${index}, 'title', this.value)">
                        </div>

                        <div class="bsc-step-metrics">
                            <div class="bsc-metric">
                                <span class="bsc-metric-label">Durée</span>
                                <div class="bsc-metric-input-wrapper">
                                    <input type="number" class="bsc-metric-input" value="${esc(step.duration)}" onchange="bscUpdateStep(${index}, 'duration', this.value)">
                                    <span class="bsc-metric-unit">min</span>
                                </div>
                            </div>
                            <div class="bsc-metric">
                                <span class="bsc-metric-label">Temp</span>
                                <div class="bsc-metric-input-wrapper">
                                    <input type="text" class="bsc-metric-input" value="${esc(step.temp)}" onchange="bscUpdateStep(${index}, 'temp', this.value)">
                                    <span class="bsc-metric-unit">°C</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bsc-items-container">
                        ${step.items.map((item, itemIndex) => `
                            <div class="bsc-item-row">
                                <select class="bsc-item-select" onchange="bscUpdateItem(${index}, ${itemIndex}, 'type', this.value)">
                                    <option value="Malt" ${item.type === 'Malt' ? 'selected' : ''}>Malt</option>
                                    <option value="Hop" ${item.type === 'Hop' ? 'selected' : ''}>Houblon</option>
                                    <option value="Yeast" ${item.type === 'Yeast' ? 'selected' : ''}>Levure</option>
                                    <option value="Adjunct" ${item.type === 'Adjunct' ? 'selected' : ''}>Autre</option>
                                    <option value="Technique" ${item.type === 'Technique' ? 'selected' : ''}>Technique</option>
                                    <option value="Equipment" ${item.type === 'Equipment' ? 'selected' : ''}>Matériel</option>
                                </select>
                                <input type="text" class="bsc-item-input" value="${esc(item.name)}" placeholder="Nom" onchange="bscUpdateItem(${index}, ${itemIndex}, 'name', this.value)">
                                <input type="text" class="bsc-item-input" value="${esc(item.qty)}" placeholder="Qté" onchange="bscUpdateItem(${index}, ${itemIndex}, 'qty', this.value)">
                                <button type="button" class="bsc-btn-icon" onclick="bscRemoveItem(${index}, ${itemIndex})">&times;</button>
                            </div>
                        `).join('')}
                        <button type="button" class="bsc-btn-add-item" onclick="bscAddItem(${index})">+ Ajouter un élément</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', stepHtml);
        });

        const addBtn = `<div class="bsc-add-step-wrapper"><button type="button" class="bsc-btn-add-step" onclick="bscAddStep()">+ Ajouter une étape</button></div>`;
        container.insertAdjacentHTML('beforeend', addBtn);

        updateHiddenInputs();
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
        if (t.includes('boil') || t.includes('ébullition') || t.includes('ebullition')) return 'boil';
        if (t.includes('ferment')) return 'ferment';
        if (t.includes('bottle') || t.includes('bouteille') || t.includes('fût') || t.includes('keg') || t.includes('package')) return 'package';
        return 'prep';
    }

    function getIcon(type) {
        switch(type) {
            case 'mash': return '🌾'; // Ear of Rice (closest to grain)
            case 'boil': return '🔥'; // Fire
            case 'ferment': return '⚗️'; // Alembic (closest to fermentation/chemistry)
            case 'package': return '📦'; // Package
            case 'prep': default: return '⚙️'; // Gear
        }
    }

    // Global Handlers
    window.bscAddStep = function() {
        steps.push({ id: Date.now(), title: '', type: 'prep', duration: '', temp: '', items: [] });
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
        // Auto-update type if title changes and type wasn't manually set?
        // For now, let's keep it simple. If title changes, we don't force type change unless it's new.
        if (key === 'title' && steps[index].type === 'prep' && value) {
             const detected = detectType(value);
             if (detected !== 'prep') steps[index].type = detected;
        }
        render(); // Re-render to update icon/styles
    };

    window.bscAddItem = function(stepIndex) {
        steps[stepIndex].items.push({ type: 'Malt', name: '', qty: '' });
        render();
    };

    window.bscRemoveItem = function(stepIndex, itemIndex) {
        steps[stepIndex].items.splice(itemIndex, 1);
        render();
    };

    window.bscUpdateItem = function(stepIndex, itemIndex, key, value) {
        steps[stepIndex].items[itemIndex][key] = value;
        updateHiddenInputs();
    };

    function updateHiddenInputs() {
        document.getElementById('bsc_recipe_steps').value = JSON.stringify(steps);

        let malts = [];
        let hops = [];
        let mash = [];
        let ingredients = [];
        let equipment = [];

        steps.forEach(step => {
            if (step.title || step.duration) {
                mash.push(`${step.title}: ${step.duration}min @ ${step.temp}°C`);
            }
            step.items.forEach(item => {
                const line = `${item.name} (${item.qty})`;
                if (item.type === 'Malt') malts.push(line);
                if (item.type === 'Hop') hops.push(line);
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
