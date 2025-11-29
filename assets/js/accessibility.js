// Culture Radar - Accessibility Features
class AccessibilityWidget {
    constructor() {
        this.settings = this.loadSettings();
        this.init();
    }

    init() {
        this.createWidget();
        this.attachEventListeners();
        this.applySettings();
        this.createColorBlindFilters();
    }

    createWidget() {
        const widget = document.createElement('div');
        widget.className = 'accessibility-widget';
        widget.innerHTML = `
            <button class="accessibility-toggle" aria-label="Options d'accessibilité" title="Options d'accessibilité">
                ♿
            </button>
            <div class="accessibility-panel" role="dialog" aria-label="Panneau d'accessibilité">
                <h3>♿ Accessibilité</h3>
                
                <!-- Vision -->
                <div class="accessibility-section">
                    <h4>Vision</h4>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🔍 Taille du texte</span>
                        </label>
                        <div class="font-size-controls">
                            <button class="font-size-btn" data-size="normal">Normal</button>
                            <button class="font-size-btn" data-size="large">Grand</button>
                            <button class="font-size-btn" data-size="extra-large">Très grand</button>
                        </div>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🌓 Contraste élevé</span>
                            <input type="checkbox" id="high-contrast">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🔗 Souligner les liens</span>
                            <input type="checkbox" id="underline-links">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🎯 Focus amélioré</span>
                            <input type="checkbox" id="enhanced-focus">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🖱️ Grand curseur</span>
                            <input type="checkbox" id="large-cursor">
                        </label>
                    </div>
                </div>
                
                <!-- Lecture -->
                <div class="accessibility-section">
                    <h4>Lecture & Dyslexie</h4>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>📖 Police dyslexie</span>
                            <input type="checkbox" id="dyslexia-mode">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>📏 Guide de lecture</span>
                            <input type="checkbox" id="reading-guide">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>↔️ Espacement augmenté</span>
                            <input type="checkbox" id="increased-spacing">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🎨 Mode TDAH</span>
                            <input type="checkbox" id="adhd-friendly">
                        </label>
                    </div>
                </div>
                
                <!-- Daltonisme -->
                <div class="accessibility-section">
                    <h4>Daltonisme</h4>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🔴 Protanopie (rouge)</span>
                            <input type="radio" name="colorblind" value="protanopia">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🟢 Deutéranopie (vert)</span>
                            <input type="radio" name="colorblind" value="deuteranopia">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>🔵 Tritanopie (bleu)</span>
                            <input type="radio" name="colorblind" value="tritanopia">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>⚫ Monochrome</span>
                            <input type="radio" name="colorblind" value="monochrome">
                        </label>
                    </div>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>👁️ Vision normale</span>
                            <input type="radio" name="colorblind" value="normal" checked>
                        </label>
                    </div>
                </div>
                
                <!-- Animations -->
                <div class="accessibility-section">
                    <h4>Animations</h4>
                    
                    <div class="accessibility-option">
                        <label>
                            <span>⏸️ Réduire les mouvements</span>
                            <input type="checkbox" id="reduce-motion">
                        </label>
                    </div>
                </div>
                
                <button class="reset-btn" onclick="accessibilityWidget.resetSettings()">
                    🔄 Réinitialiser tout
                </button>
            </div>
        `;
        
        document.body.appendChild(widget);
        
        // Create reading guide element
        const readingGuide = document.createElement('div');
        readingGuide.className = 'reading-guide';
        document.body.appendChild(readingGuide);
    }

    attachEventListeners() {
        // Toggle panel
        document.querySelector('.accessibility-toggle').addEventListener('click', () => {
            document.querySelector('.accessibility-panel').classList.toggle('active');
        });
        
        // Close panel when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.accessibility-widget')) {
                document.querySelector('.accessibility-panel').classList.remove('active');
            }
        });
        
        // Font size controls
        document.querySelectorAll('.font-size-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.setFontSize(e.target.dataset.size);
            });
        });
        
        // Checkboxes
        const checkboxOptions = [
            'high-contrast', 'dyslexia-mode', 'reading-guide', 
            'increased-spacing', 'enhanced-focus', 'reduce-motion',
            'underline-links', 'large-cursor', 'adhd-friendly'
        ];
        
        checkboxOptions.forEach(option => {
            const checkbox = document.getElementById(option);
            if (checkbox) {
                checkbox.addEventListener('change', (e) => {
                    this.toggleOption(option, e.target.checked);
                });
            }
        });
        
        // Color blind modes
        document.querySelectorAll('input[name="colorblind"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.setColorBlindMode(e.target.value);
            });
        });
        
        // Reading guide mouse tracking
        document.addEventListener('mousemove', (e) => {
            if (this.settings.readingGuide) {
                const guide = document.querySelector('.reading-guide');
                guide.style.top = (e.clientY - 10) + 'px';
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Alt + A to open accessibility panel
            if (e.altKey && e.key === 'a') {
                e.preventDefault();
                document.querySelector('.accessibility-panel').classList.toggle('active');
            }
            
            // Alt + D for dyslexia mode
            if (e.altKey && e.key === 'd') {
                e.preventDefault();
                const checkbox = document.getElementById('dyslexia-mode');
                checkbox.checked = !checkbox.checked;
                this.toggleOption('dyslexia-mode', checkbox.checked);
            }
            
            // Alt + C for high contrast
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                const checkbox = document.getElementById('high-contrast');
                checkbox.checked = !checkbox.checked;
                this.toggleOption('high-contrast', checkbox.checked);
            }
        });
    }

    toggleOption(option, enabled) {
        const bodyClass = option.replace(/-/g, '-');
        
        if (enabled) {
            document.body.classList.add(bodyClass);
        } else {
            document.body.classList.remove(bodyClass);
        }
        
        // Special handling for reading guide
        if (option === 'reading-guide') {
            if (enabled) {
                document.body.classList.add('reading-guide-active');
            } else {
                document.body.classList.remove('reading-guide-active');
            }
        }
        
        this.settings[this.toCamelCase(option)] = enabled;
        this.saveSettings();
        
        // Announce change to screen readers
        this.announce(`${this.getOptionLabel(option)} ${enabled ? 'activé' : 'désactivé'}`);
    }

    setFontSize(size) {
        // Remove all size classes
        document.body.classList.remove('large-text', 'extra-large-text');
        
        // Update buttons
        document.querySelectorAll('.font-size-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.size === size) {
                btn.classList.add('active');
            }
        });
        
        // Apply new size
        if (size === 'large') {
            document.body.classList.add('large-text');
        } else if (size === 'extra-large') {
            document.body.classList.add('extra-large-text');
        }
        
        this.settings.fontSize = size;
        this.saveSettings();
        
        // Announce change
        const sizeLabels = {
            'normal': 'normale',
            'large': 'grande',
            'extra-large': 'très grande'
        };
        this.announce(`Taille du texte ${sizeLabels[size]}`);
    }

    setColorBlindMode(mode) {
        // Remove all color blind classes
        document.body.classList.remove('protanopia', 'deuteranopia', 'tritanopia', 'monochrome');
        
        // Apply new mode
        if (mode !== 'normal') {
            document.body.classList.add(mode);
        }
        
        this.settings.colorBlindMode = mode;
        this.saveSettings();
        
        // Announce change
        const modeLabels = {
            'normal': 'normale',
            'protanopia': 'protanopie',
            'deuteranopia': 'deutéranopie',
            'tritanopia': 'tritanopie',
            'monochrome': 'monochrome'
        };
        this.announce(`Mode de vision ${modeLabels[mode]}`);
    }

    createColorBlindFilters() {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.style.display = 'none';
        svg.innerHTML = `
            <defs>
                <!-- Protanopia filter -->
                <filter id="protanopia-filter">
                    <feColorMatrix type="matrix" values="
                        0.567, 0.433, 0,     0, 0
                        0.558, 0.442, 0,     0, 0
                        0,     0.242, 0.758, 0, 0
                        0,     0,     0,     1, 0"/>
                </filter>
                
                <!-- Deuteranopia filter -->
                <filter id="deuteranopia-filter">
                    <feColorMatrix type="matrix" values="
                        0.625, 0.375, 0,   0, 0
                        0.7,   0.3,   0,   0, 0
                        0,     0.3,   0.7, 0, 0
                        0,     0,     0,   1, 0"/>
                </filter>
                
                <!-- Tritanopia filter -->
                <filter id="tritanopia-filter">
                    <feColorMatrix type="matrix" values="
                        0.95, 0.05,  0,     0, 0
                        0,    0.433, 0.567, 0, 0
                        0,    0.475, 0.525, 0, 0
                        0,    0,     0,     1, 0"/>
                </filter>
            </defs>
        `;
        document.body.appendChild(svg);
    }

    loadSettings() {
        const saved = localStorage.getItem('accessibilitySettings');
        return saved ? JSON.parse(saved) : {
            fontSize: 'normal',
            highContrast: false,
            dyslexiaMode: false,
            readingGuide: false,
            increasedSpacing: false,
            enhancedFocus: false,
            reduceMotion: false,
            underlineLinks: false,
            largeCursor: false,
            adhdFriendly: false,
            colorBlindMode: 'normal'
        };
    }

    saveSettings() {
        localStorage.setItem('accessibilitySettings', JSON.stringify(this.settings));
    }

    applySettings() {
        // Apply saved settings on load
        Object.keys(this.settings).forEach(key => {
            if (key === 'fontSize') {
                if (this.settings[key] !== 'normal') {
                    this.setFontSize(this.settings[key]);
                }
            } else if (key === 'colorBlindMode') {
                if (this.settings[key] !== 'normal') {
                    this.setColorBlindMode(this.settings[key]);
                    document.querySelector(`input[name="colorblind"][value="${this.settings[key]}"]`).checked = true;
                }
            } else {
                const kebabKey = this.toKebabCase(key);
                if (this.settings[key]) {
                    document.body.classList.add(kebabKey);
                    const checkbox = document.getElementById(kebabKey);
                    if (checkbox) checkbox.checked = true;
                    
                    if (key === 'readingGuide') {
                        document.body.classList.add('reading-guide-active');
                    }
                }
            }
        });
    }

    resetSettings() {
        // Remove all classes
        const classes = [
            'large-text', 'extra-large-text', 'high-contrast', 
            'dyslexia-mode', 'reading-guide-active', 'increased-spacing',
            'enhanced-focus', 'reduce-motion', 'underline-links',
            'large-cursor', 'adhd-friendly', 'protanopia', 
            'deuteranopia', 'tritanopia', 'monochrome'
        ];
        
        classes.forEach(cls => document.body.classList.remove(cls));
        
        // Reset all controls
        document.querySelectorAll('.accessibility-panel input[type="checkbox"]').forEach(cb => {
            cb.checked = false;
        });
        
        document.querySelector('input[name="colorblind"][value="normal"]').checked = true;
        
        document.querySelectorAll('.font-size-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.size === 'normal') {
                btn.classList.add('active');
            }
        });
        
        // Clear saved settings
        this.settings = {
            fontSize: 'normal',
            highContrast: false,
            dyslexiaMode: false,
            readingGuide: false,
            increasedSpacing: false,
            enhancedFocus: false,
            reduceMotion: false,
            underlineLinks: false,
            largeCursor: false,
            adhdFriendly: false,
            colorBlindMode: 'normal'
        };
        
        this.saveSettings();
        this.announce('Paramètres d\'accessibilité réinitialisés');
    }

    announce(message) {
        // Create announcement for screen readers
        const announcement = document.createElement('div');
        announcement.className = 'sr-only';
        announcement.setAttribute('role', 'status');
        announcement.setAttribute('aria-live', 'polite');
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    getOptionLabel(option) {
        const labels = {
            'high-contrast': 'Contraste élevé',
            'dyslexia-mode': 'Mode dyslexie',
            'reading-guide': 'Guide de lecture',
            'increased-spacing': 'Espacement augmenté',
            'enhanced-focus': 'Focus amélioré',
            'reduce-motion': 'Réduction des mouvements',
            'underline-links': 'Soulignement des liens',
            'large-cursor': 'Grand curseur',
            'adhd-friendly': 'Mode TDAH'
        };
        return labels[option] || option;
    }

    toCamelCase(str) {
        return str.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    }

    toKebabCase(str) {
        return str.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase();
    }
}

// Initialize widget when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.accessibilityWidget = new AccessibilityWidget();
    });
} else {
    window.accessibilityWidget = new AccessibilityWidget();
}