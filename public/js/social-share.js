/**
 * Fonctions améliorées pour le partage social
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les boutons de partage
    initShareButtons();
    
    // Initialiser les animations pour les cartes de post
    initPostCardAnimations();
    
    // Initialiser les interactions pour les boutons d'action
    initActionButtons();
});

/**
 * Initialise les boutons de partage avec des animations
 */
function initShareButtons() {
    // Gérer l'affichage des options de partage
    const shareToggles = document.querySelectorAll('.btn-share');
    
    shareToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const shareOptions = this.closest('.post-actions-simple').querySelector('.share-options');
            
            // Fermer tous les autres menus de partage ouverts
            document.querySelectorAll('.share-options.show').forEach(menu => {
                if (menu !== shareOptions) {
                    menu.classList.remove('show');
                }
            });
            
            // Basculer l'affichage du menu actuel
            shareOptions.classList.toggle('show');
            
            // Animation du bouton de partage
            this.classList.add('pulse-animation');
            setTimeout(() => {
                this.classList.remove('pulse-animation');
            }, 300);
        });
    });
    
    // Fermer les menus de partage lorsqu'on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.share-dropdown') && !e.target.closest('.btn-share')) {
            document.querySelectorAll('.share-options.show').forEach(menu => {
                menu.classList.remove('show');
            });
        }
    });
    
    // Initialiser les boutons de partage spécifiques
    initFacebookShare();
    initTwitterShare();
    initLinkedInShare();
    initWhatsAppShare();
}

/**
 * Initialise les animations pour les cartes de post
 */
function initPostCardAnimations() {
    const postCards = document.querySelectorAll('.post-card-grid');
    
    // Appliquer des délais d'animation différents pour créer un effet cascade
    postCards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
    
    // Ajouter des effets de survol améliorés
    postCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.boxShadow = '0 22px 27px -5px rgba(0, 0, 0, 0.13), 0 12px 12px -5px rgba(0, 0, 0, 0.06)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
        });
    });
}

/**
 * Initialise les interactions pour les boutons d'action
 */
function initActionButtons() {
    // Gérer les boutons "J'aime"
    const likeButtons = document.querySelectorAll('.btn-like');
    
    likeButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Basculer la classe 'liked'
            this.classList.toggle('liked');
            
            // Mettre à jour le compteur de likes
            const likeCount = this.closest('.post-actions-simple').querySelector('.like-stat');
            if (likeCount) {
                let count = parseInt(likeCount.getAttribute('data-count') || '0');
                
                if (this.classList.contains('liked')) {
                    count++;
                    // Animation de cœur
                    createHeartAnimation(this);
                } else {
                    count = Math.max(0, count - 1);
                }
                
                likeCount.setAttribute('data-count', count);
                likeCount.textContent = count + (count === 1 ? ' personne aime ça' : ' personnes aiment ça');
            }
        });
    });
    
    // Gérer les boutons de commentaire
    const commentButtons = document.querySelectorAll('.btn-comment');
    
    commentButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Trouver et mettre le focus sur le champ de commentaire
            const commentForm = this.closest('.post-actions-simple').querySelector('.comment-form-simple');
            if (commentForm) {
                const commentInput = commentForm.querySelector('input');
                if (commentInput) {
                    commentInput.focus();
                    
                    // Faire défiler jusqu'au formulaire de commentaire
                    commentForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
}

/**
 * Crée une animation de cœur lorsqu'un utilisateur aime un post
 */
function createHeartAnimation(button) {
    const heart = document.createElement('i');
    heart.className = 'fas fa-heart heart-animation';
    heart.style.position = 'absolute';
    heart.style.color = 'var(--danger-color)';
    heart.style.fontSize = '1.5rem';
    heart.style.opacity = '0';
    heart.style.pointerEvents = 'none';
    
    // Positionner le cœur au centre du bouton
    const rect = button.getBoundingClientRect();
    heart.style.left = `${rect.left + rect.width / 2}px`;
    heart.style.top = `${rect.top + rect.height / 2}px`;
    
    // Ajouter au DOM
    document.body.appendChild(heart);
    
    // Animer le cœur
    heart.animate([
        { transform: 'translate(-50%, -50%) scale(0)', opacity: 0.8 },
        { transform: 'translate(-50%, -150%) scale(1.5)', opacity: 0 }
    ], {
        duration: 700,
        easing: 'cubic-bezier(0.1, 0.7, 1.0, 0.1)'
    }).onfinish = () => {
        heart.remove();
    };
}

/**
 * Partage sur Facebook
 */
function initFacebookShare() {
    const facebookButtons = document.querySelectorAll('.share-btn.facebook');
    
    facebookButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postUrl = this.getAttribute('data-url') || window.location.href;
            const shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(postUrl)}`;
            
            window.open(shareUrl, 'facebook-share', 'width=580,height=296');
            
            // Fermer le menu de partage
            this.closest('.share-options').classList.remove('show');
            
            // Afficher une notification de succès
            showShareNotification('Facebook');
        });
    });
}

/**
 * Partage sur Twitter
 */
function initTwitterShare() {
    const twitterButtons = document.querySelectorAll('.share-btn.twitter');
    
    twitterButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postUrl = this.getAttribute('data-url') || window.location.href;
            const text = this.getAttribute('data-text') || '';
            const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(postUrl)}`;
            
            window.open(shareUrl, 'twitter-share', 'width=550,height=235');
            
            // Fermer le menu de partage
            this.closest('.share-options').classList.remove('show');
            
            // Afficher une notification de succès
            showShareNotification('Twitter');
        });
    });
}

/**
 * Partage sur LinkedIn
 */
function initLinkedInShare() {
    const linkedinButtons = document.querySelectorAll('.share-btn.linkedin');
    
    linkedinButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postUrl = this.getAttribute('data-url') || window.location.href;
            const shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(postUrl)}`;
            
            window.open(shareUrl, 'linkedin-share', 'width=750,height=600');
            
            // Fermer le menu de partage
            this.closest('.share-options').classList.remove('show');
            
            // Afficher une notification de succès
            showShareNotification('LinkedIn');
        });
    });
}

/**
 * Partage sur WhatsApp
 */
function initWhatsAppShare() {
    const whatsappButtons = document.querySelectorAll('.share-btn.whatsapp');
    
    whatsappButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const postUrl = this.getAttribute('data-url') || window.location.href;
            const text = this.getAttribute('data-text') || '';
            const shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text + ' ' + postUrl)}`;
            
            window.open(shareUrl, 'whatsapp-share', 'width=580,height=296');
            
            // Fermer le menu de partage
            this.closest('.share-options').classList.remove('show');
            
            // Afficher une notification de succès
            showShareNotification('WhatsApp');
        });
    });
}

/**
 * Affiche une notification de partage réussi
 */
function showShareNotification(platform) {
    // Créer l'élément de notification
    const notification = document.createElement('div');
    notification.className = 'share-notification';
    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>Partagé sur ${platform} avec succès!</span>
    `;
    
    // Styles pour la notification
    notification.style.position = 'fixed';
    notification.style.bottom = '20px';
    notification.style.right = '20px';
    notification.style.backgroundColor = 'var(--success-color)';
    notification.style.color = 'white';
    notification.style.padding = '12px 20px';
    notification.style.borderRadius = '8px';
    notification.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
    notification.style.display = 'flex';
    notification.style.alignItems = 'center';
    notification.style.gap = '10px';
    notification.style.zIndex = '9999';
    notification.style.transform = 'translateY(100px)';
    notification.style.opacity = '0';
    notification.style.transition = 'all 0.3s ease';
    
    // Ajouter au DOM
    document.body.appendChild(notification);
    
    // Animer l'entrée
    setTimeout(() => {
        notification.style.transform = 'translateY(0)';
        notification.style.opacity = '1';
    }, 10);
    
    // Supprimer après 3 secondes
    setTimeout(() => {
        notification.style.transform = 'translateY(100px)';
        notification.style.opacity = '0';
        
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}