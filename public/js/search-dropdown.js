/**
 * Fonctions améliorées pour la recherche en temps réel
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les champs de recherche (y compris dans les barres de navigation)
    const searchInputs = document.querySelectorAll('.search-input, .navbar-search input[type="text"]');
    
    // Initialiser les filtres de recherche
    initSearchFilters();
    
    // Initialiser les boutons de nettoyage
    initClearSearchButton();
    
    // Appliquer le filtre initial si présent dans l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const initialFilter = urlParams.get('filter');
    if (initialFilter) {
        const filterButton = document.querySelector(`.filter-btn[data-filter="${initialFilter}"]`);
        if (filterButton) {
            filterButton.click();
        }
    }
    
    // Initialiser la recherche en temps réel
    initRealTimeSearch();
    
    // Initialiser les animations pour les résultats de recherche
    initSearchResultAnimations();
});

/**
 * Initialise la recherche en temps réel avec des animations
 */
function initRealTimeSearch() {
    // Sélectionner tous les champs de recherche (barre principale et barre de navigation)
    const searchInputs = document.querySelectorAll('.search-input, .nav-search-input');
    
    searchInputs.forEach(input => {
        // Trouver le conteneur de dropdown approprié
        const searchDropdown = input.closest('.search-container, .nav-center')?.querySelector('.search-dropdown, .search-results-dropdown') || document.querySelector('.search-results-dropdown');
        const loadingIndicator = input.closest('.search-container, .nav-search-wrapper')?.querySelector('.search-loading') || createLoadingIndicator(input);
        
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
                if (!document.activeElement.closest('.search-dropdown, .search-results-dropdown')) {
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
                // Afficher le bouton de nettoyage si disponible
                const clearButton = input.parentNode.querySelector('.clear-search');
                if (clearButton) {
                    clearButton.style.display = 'flex';
                }
            } else {
                loadingIndicator.style.opacity = '0';
                searchDropdown.classList.remove('show');
                // Cacher le bouton de nettoyage si disponible
                const clearButton = input.parentNode.querySelector('.clear-search');
                if (clearButton) {
                    clearButton.style.display = 'none';
                }
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
        
        // Gérer la soumission du formulaire
        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const query = input.value.trim();
                if (query.length < 2) {
                    e.preventDefault();
                    input.focus();
                }
            });
        }
    });
    
    // Fermer les dropdowns lorsqu'on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container, .nav-search-wrapper')) {
            document.querySelectorAll('.search-dropdown.show, .search-results-dropdown.show').forEach(dropdown => {
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
    loadingIndicator.style.zIndex = '5';
    
    // Ajouter au DOM
    input.parentNode.style.position = 'relative';
    input.parentNode.appendChild(loadingIndicator);
    
    return loadingIndicator;
}

/**
 * Initialise les filtres de recherche
 */
function initSearchFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    if (!filterButtons.length) return;
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer la classe active de tous les boutons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            this.classList.add('active');
            
            // Récupérer le filtre
            const filter = this.getAttribute('data-filter');
            
            // Appliquer le filtre
            applyFilter(filter);
        });
    });
}

/**
 * Applique un filtre aux résultats de recherche
 */
function applyFilter(filter) {
    const userSection = document.querySelector('.users-grid')?.closest('.results-section');
    const groupSection = document.querySelector('.groups-grid')?.closest('.results-section');
    
    if (!userSection && !groupSection) return;
    
    switch (filter) {
        case 'all':
            if (userSection) userSection.style.display = 'block';
            if (groupSection) groupSection.style.display = 'block';
            break;
        case 'users':
            if (userSection) userSection.style.display = 'block';
            if (groupSection) groupSection.style.display = 'none';
            break;
        case 'groups':
            if (userSection) userSection.style.display = 'none';
            if (groupSection) groupSection.style.display = 'block';
            break;
    }
    
    // Mettre à jour l'URL avec le filtre
    const url = new URL(window.location.href);
    url.searchParams.set('filter', filter);
    window.history.replaceState({}, '', url);
}

/**
 * Initialise le bouton de nettoyage de recherche
 */
function initClearSearchButton() {
    const clearButtons = document.querySelectorAll('.clear-search');
    
    clearButtons.forEach(button => {
        // Vérifier si le champ de recherche est vide
        const input = button.parentNode.querySelector('input[type="text"]');
        button.style.display = input && input.value.trim() ? 'flex' : 'none';
        
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('input[type="text"]');
            if (input) {
                input.value = '';
                input.focus();
                this.style.display = 'none';
                
                // Fermer les dropdowns
                const dropdown = document.querySelector('.search-dropdown.show, .search-results-dropdown.show');
                if (dropdown) dropdown.classList.remove('show');
            }
        });
    });
}

/**
 * Fonction pour nettoyer la recherche (appelée depuis le HTML)
 */
function clearSearch(button) {
    const input = button.parentNode.querySelector('input[type="text"]');
    if (input) {
        input.value = '';
        input.focus();
        button.style.display = 'none';
    }
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
            
            // Vérifier si des résultats ont été trouvés
            if ((!data.users || data.users.length === 0) && (!data.groups || data.groups.length === 0)) {
                searchDropdown.innerHTML = `
                    <div class="search-no-results">
                        <p>No results for  "${query}"</p>
                        <span>Try other keywords</span>
                    </div>
                `;
                searchDropdown.classList.add('show');
                return;
            }
            
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
    if (data.users && data.users.length > 0) {
        const usersSection = document.createElement('div');
        usersSection.className = 'search-dropdown-section';
        
        const usersHeader = document.createElement('div');
        usersHeader.className = 'search-dropdown-header';
        usersHeader.textContent = 'Utilisateurs';
        usersSection.appendChild(usersHeader);
        
        data.users.forEach(user => {
            const userItem = document.createElement('a');
            userItem.className = 'search-dropdown-item';
            userItem.href = `/profile/${user.id}`;
            
            userItem.innerHTML = `
                <img src="${user.avatar || '/images/default-avatar.svg'}" alt="${user.username}">
                <div class="item-info">
                    <div class="item-name">${user.username}</div>
                    <div class="item-meta">${user.email || ''}</div>
                </div>
                <span class="item-type user">Utilisateur</span>
            `;
            
            usersSection.appendChild(userItem);
        });
        
        searchDropdown.appendChild(usersSection);
    }
    
    if (data.groups && data.groups.length > 0) {
        const groupsSection = document.createElement('div');
        groupsSection.className = 'search-dropdown-section';
        
        const groupsHeader = document.createElement('div');
        groupsHeader.className = 'search-dropdown-header';
        groupsHeader.textContent = 'Groupes';
        groupsSection.appendChild(groupsHeader);
        
        data.groups.forEach(group => {
            const groupItem = document.createElement('a');
            groupItem.className = 'search-dropdown-item';
            groupItem.href = `/group/${group.id}`;
            
            groupItem.innerHTML = `
                <img src="${group.avatar || '/images/default-group.svg'}" alt="${group.name}">
                <div class="item-info">
                    <div class="item-name">${group.name}</div>
                    <div class="item-meta">${group.description ? group.description.substring(0, 50) + (group.description.length > 50 ? '...' : '') : ''}</div>
                </div>
                <span class="item-type group">Groupe</span>
            `;
            
            groupsSection.appendChild(groupItem);
        });
        
        searchDropdown.appendChild(groupsSection);
    }
    
    // Ajouter un lien pour voir tous les résultats
    if ((data.users && data.users.length > 0) || (data.groups && data.groups.length > 0)) {
        const viewAllSection = document.createElement('div');
        viewAllSection.className = 'search-dropdown-section view-all-section';
        
        const viewAllLink = document.createElement('a');
        viewAllLink.className = 'search-dropdown-item view-all';
        viewAllLink.href = `/search?search=${encodeURIComponent(document.querySelector('.nav-search-input').value)}`;
        
        viewAllLink.innerHTML = `
            <div class="item-info">
                <div class="item-name">Voir tous les résultats</div>
            </div>
            <i class="fas fa-arrow-right"></i>
        `;
        
        viewAllSection.appendChild(viewAllLink);
        searchDropdown.appendChild(viewAllSection);
        searchDropdown.classList.add('show');
    } else {
        searchDropdown.innerHTML = `
            <div class="search-no-results">
                <p>Aucun résultat trouvé</p>
                <span>Essayez avec d'autres termes</span>
            </div>
        `;
        searchDropdown.classList.add('show');
    }
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