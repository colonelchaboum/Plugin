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
            duration: '',
            temp: '',
            items: []
        }];
    }

    // Render Function
    function render() {
        container.innerHTML = '';

        steps.forEach((step, index) => {
            const stepHtml = `
                <div class="bsc-step-card" data-index="${index}">
                    <button type="button" class="bsc-remove-step" onclick="bscRemoveStep(${index})">&times;</button>
                    <div class="bsc-step-header">
                        <input type="text" class="bsc-step-title-input" value="${esc(step.title)}" placeholder="Nom de l'étape (ex: Ébullition)" onchange="bscUpdateStep(${index}, 'title', this.value)">
                        <div class="bsc-step-meta">
                            <div class="bsc-input-group">
                                <label>Durée (min)</label>
                                <input type="number" class="bsc-small-input" value="${esc(step.duration)}" onchange="bscUpdateStep(${index}, 'duration', this.value)">
                            </div>
                            <div class="bsc-input-group">
                                <label>Temp (°C)</label>
                                <input type="text" class="bsc-small-input" value="${esc(step.temp)}" onchange="bscUpdateStep(${index}, 'temp', this.value)">
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
                                <input type="text" class="bsc-item-input" value="${esc(item.name)}" placeholder="Nom (ex: Citra)" onchange="bscUpdateItem(${index}, ${itemIndex}, 'name', this.value)">
                                <input type="text" class="bsc-item-input" value="${esc(item.qty)}" placeholder="Qté/Note" onchange="bscUpdateItem(${index}, ${itemIndex}, 'qty', this.value)">
                                <button type="button" class="bsc-btn-icon" onclick="bscRemoveItem(${index}, ${itemIndex})">&times;</button>
                            </div>
                        `).join('')}
                        <button type="button" class="bsc-btn-add-item" onclick="bscAddItem(${index})">+ Ajouter un élément</button>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', stepHtml);
        });

        const addBtn = `<button type="button" class="bsc-btn-add-step" onclick="bscAddStep()">+ Ajouter une étape</button>`;
        container.insertAdjacentHTML('beforeend', addBtn);

        updateHiddenInputs();
    }

    // Helpers
    function esc(str) {
        if (!str) return '';
        return str.replace(/"/g, '&quot;');
    }

    // Global Handlers (attached to window for simplicity with inline onclicks)
    window.bscAddStep = function() {
        steps.push({ id: Date.now(), title: '', duration: '', temp: '', items: [] });
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
        updateHiddenInputs();
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
        // Main JSON
        document.getElementById('bsc_recipe_steps').value = JSON.stringify(steps);

        // Aggregates for Supabase / Legacy Text fields
        let malts = [];
        let hops = [];
        let mash = [];
        let ingredients = [];
        let equipment = [];

        steps.forEach(step => {
            // Mash Schedule Summary
            if (step.title || step.duration) {
                mash.push(`${step.title}: ${step.duration}min @ ${step.temp}°C`);
            }

            step.items.forEach(item => {
                const line = `${item.name} (${item.qty})`;
                if (item.type === 'Malt') malts.push(line);
                if (item.type === 'Hop') hops.push(line);
                if (item.type === 'Equipment') equipment.push(line);

                // All ingredients
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

    // Initial Render
    render();
});
