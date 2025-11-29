/**
 * CultureRadar AI Recommendations JavaScript
 * Handles AI-powered recommendation interactions and feedback
 */

class AIRecommendations {
    constructor() {
        this.apiBase = '/api/recommendations.php';
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.startBehaviorTracking();
    }
    
    /**
     * Bind event listeners for AI interactions
     */
    bindEvents() {
        // Track event card views
        this.observeEventCards();
        
        // Track clicks on event details
        document.addEventListener('click', (e) => {
            if (e.target.closest('.event-card')) {
                const eventCard = e.target.closest('.event-card');
                const eventId = this.extractEventId(eventCard);
                if (eventId) {
                    this.recordInteraction(eventId, 'view');
                }
            }
            
            // Track "Voir détails" clicks
            if (e.target.closest('a[href*="/event.php"]')) {
                const link = e.target.closest('a[href*="/event.php"]');
                const eventId = this.extractEventIdFromUrl(link.getAttribute('href'));
                if (eventId) {
                    this.recordInteraction(eventId, 'click');
                }
            }
        });
        
        // Track save/favorite actions
        window.saveEvent = (eventId) => {
            this.recordInteraction(eventId, 'save');
            this.originalSaveEvent(eventId);
        };
        
        // Store original save function if exists
        this.originalSaveEvent = window.saveEvent || function() {};
    }
    
    /**
     * Use Intersection Observer to track which events are actually viewed
     */
    observeEventCards() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.intersectionRatio > 0.5) {
                    const eventCard = entry.target;
                    const eventId = this.extractEventId(eventCard);
                    
                    if (eventId && !eventCard.dataset.aiViewed) {
                        eventCard.dataset.aiViewed = 'true';
                        
                        // Delay to ensure it's actually viewed
                        setTimeout(() => {
                            if (entry.isIntersecting) {
                                this.recordInteraction(eventId, 'view', {
                                    viewDuration: 2000,
                                    scrollPosition: window.scrollY
                                });
                            }
                        }, 2000);
                    }
                }
            });
        }, {
            threshold: 0.5,
            rootMargin: '0px'
        });
        
        // Observe all event cards
        document.querySelectorAll('.event-card').forEach(card => {
            observer.observe(card);
        });
    }
    
    /**
     * Record user interaction with AI system
     */
    async recordInteraction(eventId, type, metadata = {}) {
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'feedback',
                    event_id: eventId,
                    interaction_type: type,
                    metadata: metadata
                })
            });
            
            if (!response.ok) {
                console.warn('Failed to record AI interaction:', response.statusText);
            }
        } catch (error) {
            console.warn('Error recording AI interaction:', error);
        }
    }
    
    /**
     * Get fresh AI recommendations
     */
    async getRecommendations(limit = 10, excludeViewed = true) {
        try {
            const params = new URLSearchParams({
                action: 'recommend',
                limit: limit,
                exclude_viewed: excludeViewed
            });
            
            const response = await fetch(`${this.apiBase}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                return data.recommendations;
            } else {
                throw new Error(data.error || 'Failed to get recommendations');
            }
        } catch (error) {
            console.error('Error getting AI recommendations:', error);
            return [];
        }
    }
    
    /**
     * Get explanation for why an event was recommended
     */
    async getRecommendationExplanation(eventId) {
        try {
            const params = new URLSearchParams({
                action: 'explain',
                event_id: eventId
            });
            
            const response = await fetch(`${this.apiBase}?${params}`);
            const data = await response.json();
            
            if (data.success) {
                return data;
            } else {
                throw new Error(data.error || 'Failed to get explanation');
            }
        } catch (error) {
            console.error('Error getting recommendation explanation:', error);
            return null;
        }
    }
    
    /**
     * Submit explicit rating for an event
     */
    async rateEvent(eventId, rating) {
        if (rating < 1 || rating > 5) {
            throw new Error('Rating must be between 1 and 5');
        }
        
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'feedback',
                    event_id: eventId,
                    interaction_type: 'rate',
                    rating: rating
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Merci pour votre évaluation ! Cela améliore nos recommandations.', 'success');
                return true;
            } else {
                throw new Error(data.error || 'Failed to record rating');
            }
        } catch (error) {
            console.error('Error recording rating:', error);
            this.showNotification('Erreur lors de l\'enregistrement de votre évaluation.', 'error');
            return false;
        }
    }
    
    /**
     * Track behavior patterns for AI learning
     */
    startBehaviorTracking() {
        // Track time spent on page
        this.pageStartTime = Date.now();
        
        // Track scroll behavior
        let scrollTimeout;
        let maxScroll = 0;
        
        window.addEventListener('scroll', () => {
            const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
            maxScroll = Math.max(maxScroll, scrollPercent);
            
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // Record scroll pattern after user stops scrolling
                this.recordBehavior('scroll', {
                    max_scroll_percent: Math.round(maxScroll),
                    final_position: Math.round(scrollPercent)
                });
            }, 1000);
        });
        
        // Track page exit behavior
        window.addEventListener('beforeunload', () => {
            const timeSpent = Date.now() - this.pageStartTime;
            this.recordBehavior('page_exit', {
                time_spent: timeSpent,
                max_scroll: Math.round(maxScroll)
            });
        });
        
        // Track clicks on recommendations section
        const recoSection = document.querySelector('.section-card');
        if (recoSection) {
            recoSection.addEventListener('click', () => {
                this.recordBehavior('recommendations_engagement', {
                    timestamp: Date.now()
                });
            });
        }
    }
    
    /**
     * Record general behavior patterns
     */
    recordBehavior(type, data) {
        // Use localStorage to batch behavior data
        const behaviorData = JSON.parse(localStorage.getItem('ai_behavior') || '[]');
        
        behaviorData.push({
            type: type,
            data: data,
            timestamp: Date.now(),
            url: window.location.pathname
        });
        
        // Keep only last 50 behavior entries
        if (behaviorData.length > 50) {
            behaviorData.splice(0, behaviorData.length - 50);
        }
        
        localStorage.setItem('ai_behavior', JSON.stringify(behaviorData));
        
        // Send batch periodically
        if (behaviorData.length % 10 === 0) {
            this.sendBehaviorBatch();
        }
    }
    
    /**
     * Send batched behavior data to AI system
     */
    async sendBehaviorBatch() {
        const behaviorData = JSON.parse(localStorage.getItem('ai_behavior') || '[]');
        
        if (behaviorData.length === 0) return;
        
        try {
            const response = await fetch(this.apiBase, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'batch_feedback',
                    interactions: behaviorData.map(item => ({
                        type: 'behavior',
                        event_id: null,
                        metadata: {
                            behavior_type: item.type,
                            behavior_data: item.data,
                            page: item.url
                        }
                    }))
                })
            });
            
            if (response.ok) {
                // Clear sent data
                localStorage.setItem('ai_behavior', '[]');
            }
        } catch (error) {
            console.warn('Failed to send behavior batch:', error);
        }
    }
    
    /**
     * Show rating dialog for an event
     */
    showRatingDialog(eventId, eventTitle) {
        const modal = document.createElement('div');
        modal.className = 'rating-modal';
        modal.innerHTML = `
            <div class="rating-modal-content">
                <div class="rating-header">
                    <h3>Évaluez cet événement</h3>
                    <button class="close-rating" onclick="this.closest('.rating-modal').remove()">×</button>
                </div>
                <div class="rating-body">
                    <p class="event-title">${eventTitle}</p>
                    <div class="rating-stars">
                        ${[1,2,3,4,5].map(star => `
                            <button class="rating-star" data-rating="${star}">
                                <i class="fas fa-star"></i>
                            </button>
                        `).join('')}
                    </div>
                    <p class="rating-help">Cliquez sur les étoiles pour noter (1 = décevant, 5 = excellent)</p>
                </div>
                <div class="rating-actions">
                    <button class="btn-secondary" onclick="this.closest('.rating-modal').remove()">
                        Passer
                    </button>
                </div>
            </div>
        `;
        
        // Add styles
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        const content = modal.querySelector('.rating-modal-content');
        content.style.cssText = `
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            color: white;
        `;
        
        // Star rating functionality
        const stars = modal.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            star.style.cssText = `
                background: none;
                border: none;
                font-size: 2rem;
                color: rgba(255,255,255,0.3);
                cursor: pointer;
                transition: all 0.3s ease;
                margin: 0 0.25rem;
            `;
            
            star.addEventListener('mouseenter', () => {
                stars.forEach((s, i) => {
                    s.style.color = i <= index ? '#ffd700' : 'rgba(255,255,255,0.3)';
                });
            });
            
            star.addEventListener('click', async () => {
                const rating = parseInt(star.dataset.rating);
                modal.remove();
                await this.rateEvent(eventId, rating);
            });
        });
        
        modal.addEventListener('mouseleave', () => {
            stars.forEach(s => s.style.color = 'rgba(255,255,255,0.3)');
        });
        
        document.body.appendChild(modal);
    }
    
    /**
     * Create interactive recommendation widget
     */
    createRecommendationWidget() {
        const widget = document.createElement('div');
        widget.className = 'ai-recommendation-widget';
        widget.innerHTML = `
            <div class="widget-header">
                <i class="fas fa-robot"></i>
                <span>Recommandations IA</span>
                <button class="refresh-reco" title="Actualiser les recommandations">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="widget-content">
                <div class="loading">Chargement des recommandations...</div>
            </div>
        `;
        
        // Add styles
        widget.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 300px;
            background: var(--glass);
            backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 1rem;
            color: white;
            box-shadow: var(--shadow-xl);
            z-index: 1000;
            max-height: 400px;
            overflow: hidden;
        `;
        
        // Refresh functionality
        const refreshBtn = widget.querySelector('.refresh-reco');
        refreshBtn.addEventListener('click', async () => {
            refreshBtn.style.animation = 'spin 1s linear infinite';
            const recommendations = await this.getRecommendations(3);
            this.updateWidget(widget, recommendations);
            refreshBtn.style.animation = '';
        });
        
        return widget;
    }
    
    /**
     * Utility functions
     */
    extractEventId(eventCard) {
        // Try to extract event ID from various sources
        const link = eventCard.querySelector('a[href*="event.php"]');
        if (link) {
            return this.extractEventIdFromUrl(link.getAttribute('href'));
        }
        
        const saveBtn = eventCard.querySelector('button[onclick*="saveEvent"]');
        if (saveBtn) {
            const match = saveBtn.getAttribute('onclick').match(/saveEvent\((\d+)\)/);
            return match ? parseInt(match[1]) : null;
        }
        
        return null;
    }
    
    extractEventIdFromUrl(url) {
        const match = url.match(/[?&]id=(\d+)/);
        return match ? parseInt(match[1]) : null;
    }
    
    showNotification(message, type = 'info') {
        // Reuse existing notification system if available
        if (typeof showNotification === 'function') {
            showNotification(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
}

// Initialize AI Recommendations when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.aiRecommendations = new AIRecommendations();
});

// CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .rating-star:hover {
        transform: scale(1.2);
    }
    
    .ai-reasons {
        animation: fadeInUp 0.6s ease-out;
    }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
document.head.appendChild(style);