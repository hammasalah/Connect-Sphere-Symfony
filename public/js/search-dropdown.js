/**
 * Fonctions améliorées pour la recherche en temps réel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la recherche en temps réel
    initRealTimeSearch();
    
    // Initialiser les animations pour les résultats de recherche
    initSearchResultAnimations();
});

/**
 * Initialise la recherche en temps réel avec des animations
 */
function initRealTimeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        const searchDropdown = input.closest('.search-container').querySelector('.search-dropdown');
        const loadingIndicator = input.closest('.search-container').querySelector('.search-loading') || createLoadingIndicator(input);
        
        // Ajouter une animation au focus de l'input
        input.addEventListener('focus', function() {
            this.classList.add('input-focused');
            if (this.value.length >= 2) {
                searchDropdown.classList.add('show');
            }
        });
        
        // Retirer l'animation au blur de l'input
        input.addEventListener('blur', function() {
            this.classList.remove('input-focused');
            // Délai pour permettre de cliquer sur les résultats
            setTimeout(() => {
                if (!document.activeElement.closest('.search-dropdown')) {
                    searchDropdown.classList.remove('show');
                }
            }, 200);
        });
        
        // Gérer la saisie en temps réel
        let debounceTimer;
        input.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Afficher l'indicateur de chargement
            if (query.length >= 2) {
                loadingIndicator.style.opacity = '1';
            } else {
                loadingIndicator.style.opacity = '0';
                searchDropdown.classList.remove('show');
                return;
            }
            
            // Débounce pour éviter trop de requêtes
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (query.length >= 2) {
                    fetchSearchResults(query, searchDropdown, loadingIndicator);
                }
            }, 300);
        });
    });
    
    // Fermer les dropdowns lorsqu'on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            document.querySelectorAll('.search-dropdown.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });
}

/**
 * Crée un indicateur de chargement pour la recherche
 */
function createLoadingIndicator(input) {
    const loadingIndicator = document.createElement('div');
    loadingIndicator.className = 'search-loading';
    loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    // Styles pour l'indicateur
    loadingIndicator.style.position = 'absolute';
    loadingIndicator.style.right = '12px';
    loadingIndicator.style.top = '50%';
    loadingIndicator.style.transform = 'translateY(-50%)';
    loadingIndicator.style.color = 'var(--primary-color)';
    loadingIndicator.style.opacity = '0';
    loadingIndicator.style.transition = 'opacity 0.2s ease';
    
    // Ajouter au DOM
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(loadingIndicator);
    
    return loadingIndicator;
}

/**
 * Récupère les résultats de recherche via AJAX
 */
function fetchSearchResults(query, searchDropdown, loadingIndicator) {
    // URL de l'API de recherche
    const searchUrl = '/api/search?q=' + encodeURIComponent(query);
    
    // Effectuer la requête AJAX
    fetch(searchUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Masquer l'indicateur de chargement
            loadingIndicator.style.opacity = '0';
            
            // Afficher les résultats
            displaySearchResults(data, searchDropdown);
        })
        .catch(error => {
            console.error('Erreur de recherche:', error);
            loadingIndicator.style.opacity = '0';
            
            // Afficher un message d'erreur
            searchDropdown.innerHTML = `
                <div class="search-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Une erreur est survenue lors de la recherche.</p>
                </div>
            `;
            searchDropdown.classList.add('show');
        });
}

/**
 * Affiche les résultats de recherche dans le dropdown
 */
function displaySearchResults(data, searchDropdown) {
    // Vider le dropdown
    searchDropdown.innerHTML = '';
    
    // Créer les sections pour les utilisateurs et les groupes
    const usersSection = document.createElement('div');
    usersSection.className = 'search-section';
    usersSection.innerHTML = '<h4>Utilisateurs</h4>';
    
    const groupsSection = document.createElement('div');
    groupsSection.className = 'search-section';
    groupsSection.innerHTML = '<h4>Groupes</h4>';
    
    // Ajouter les utilisateurs
    if (data.users && data.users.length > 0) {
        const usersList = document.createElement('div');
        usersList.className = 'search-results';
        
        data.users.forEach(user => {
            const userItem = createSearchResultItem(user, 'user');
            usersList.appendChild(userItem);
        });
        
        usersSection.appendChild(usersList);
        searchDropdown.appendChild(usersSection);
    } else {
        usersSection.innerHTML += '<p class="no-results">Aucun utilisateur trouvé</p>';
        searchDropdown.appendChild(usersSection);
    }
    
    // Ajouter les groupes
    if (data.groups && data.groups.length > 0) {
        const groupsList = document.createElement('div');
        groupsList.className = 'search-results';
        
        data.groups.forEach(group => {
            const groupItem = createSearchResultItem(group, 'group');
            groupsList.appendChild(groupItem);
        });
        
        groupsSection.appendChild(groupsList);
        searchDropdown.appendChild(groupsSection);
    } else {
        groupsSection.innerHTML += '<p class="no-results">Aucun groupe trouvé</p>';
        searchDropdown.appendChild(groupsSection);
    }
    
    // Ajouter un bouton "Voir plus" si nécessaire
    if ((data.users && data.users.length > 5) || (data.groups && data.groups.length > 5)) {
        const viewMoreBtn = document.createElement('div');
        viewMoreBtn.className = 'view-more-btn';
        viewMoreBtn.innerHTML = 'Voir plus de résultats <i class="fas fa-arrow-right"></i>';
        
        viewMoreBtn.addEventListener('click', function() {
            // Rediriger vers la page de résultats de recherche complète
            const query = document.querySelector('.search-input').value.trim();
            window.location.href = '/search?q=' + encodeURIComponent(query);
        });
        
        searchDropdown.appendChild(viewMoreBtn);
    }
    
    // Afficher le dropdown
    searchDropdown.classList.add('show');
}

/**
 * Crée un élément de résultat de recherche
 */
function createSearchResultItem(item, type) {
    const resultItem = document.createElement('a');
    resultItem.className = 'search-result-item';
    resultItem.href = type === 'user' ? '/profile/' + item.id : '/group/' + item.id;
    
    // Ajouter une animation d'entrée
    resultItem.style.animation = 'fadeInUp 0.3s ease forwards';
    
    // Contenu de l'élément
    resultItem.innerHTML = `
        <div class="search-avatar">
            <img src="${item.avatar || '/images/default-avatar.png'}" alt="${item.name}">
        </div>
        <div class="search-info">
            <div class="search-name">${item.name}</div>
            <div class="search-bio">${item.bio || (type === 'user' ? 'Utilisateur' : 'Groupe')}</div>
        </div>
    `;
    
    // Ajouter des effets de survol
    resultItem.addEventListener('mouseenter', function() {
        this.style.backgroundColor = 'var(--hover-color)';
        this.style.transform = 'translateX(5px)';
    });
    
    resultItem.addEventListener('mouseleave', function() {
        this.style.backgroundColor = '';
        this.style.transform = 'translateX(0)';
    });
    
    return resultItem;
}

/**
 * Initialise les animations pour les résultats de recherche
 */
function initSearchResultAnimations() {
    // Ajouter des styles pour les animations
    const styleElement = document.createElement('style');
    styleElement.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .search-input.input-focused {
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.25);
        }
        
        .search-dropdown {
            transform-origin: top center;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .search-dropdown.show {
            animation: dropdownFadeIn 0.3s ease forwards;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .view-more-btn {
            padding: 10px;
            text-align: center;
            color: var(--primary-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            border-top: 1px solid var(--border-color);
        }
        
        .view-more-btn:hover {
            background-color: var(--hover-color);
        }
        
        .view-more-btn i {
            margin-left: 5px;
            transition: transform 0.2s ease;
        }
        
        .view-more-btn:hover i {
            transform: translateX(3px);
        }
        
        .no-results {
            padding: 10px;
            color: var(--text-muted);
            font-style: italic;
            text-align: center;
        }
        
        .search-error {
            padding: 15px;
            text-align: center;
            color: var(--danger-color);
        }
        
        .search-error i {
            font-size: 24px;
            margin-bottom: 10px;
        }
    `;
    
    document.head.appendChild(styleElement);
}