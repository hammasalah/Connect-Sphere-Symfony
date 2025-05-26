/**
 * Points Conversion JavaScript
 * Handles the conversion of points to money
 */

// Fonction de conversion des points
function convertPoints() {
    console.log('convertPoints function called');

    // Récupérer les valeurs du formulaire
    const pointsInput = document.getElementById('numPoints');
    const deviseSelect = document.getElementById('devise');

    if (!pointsInput || !deviseSelect) {
        console.error('Form elements not found!');
        alert('Erreur: Impossible de trouver les éléments du formulaire.');
        return;
    }

    const points = pointsInput.value;
    const devise = deviseSelect.value;

    console.log(`Points: ${points}, Devise: ${devise}`);

    // Vérifier que les points sont un nombre valide
    if (isNaN(points) || points <= 0 || points % 100 !== 0) {
        alert('Le nombre de points doit être un multiple positif de 100.');
        return;
    }

    console.log(`Conversion de ${points} points en ${devise}...`);

    // Afficher un indicateur de chargement
    const convertBtn = document.getElementById('convertBtn') || document.querySelector('.convert-button');
    if (convertBtn) {
        const originalText = convertBtn.textContent;
        convertBtn.textContent = 'Conversion en cours...';
        convertBtn.disabled = true;
    }

    // Envoyer la requête AJAX
    fetch('/points/convert/process', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            'points': points,
            'devise': devise
        })
    })
    .then(response => {
        console.log('Response received:', response);
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Data received:', data);

        if (data.success) {
            // Mettre à jour l'affichage des points et de l'argent
            const pointsElement = document.querySelector('.stat-value.points');
            const moneyElement = document.querySelector('.stat-value.money');
            const pointsDisplays = document.querySelectorAll('.user-points');

            // Calculer les nouveaux points (points actuels - points convertis)
            const newPoints = parseInt(pointsElement?.textContent || 0) - data.points;
            
            // Mettre à jour tous les affichages de points
            if (pointsElement) pointsElement.textContent = newPoints;
            pointsDisplays.forEach(el => el.textContent = newPoints);
            
            // Mettre à jour l'affichage de l'argent si disponible
            if (moneyElement) {
                const currentMoney = parseFloat(moneyElement.textContent || 0);
                moneyElement.textContent = (currentMoney + parseFloat(data.montant)).toFixed(2);
            }

            // Ajouter la nouvelle transaction à l'historique sans recharger la page
            updateConversionHistory({
                date: new Date().toISOString().split('T')[0], // Format YYYY-MM-DD
                devise: data.devise,
                pointConvertis: data.points,
                montant: data.montant
            });

            // Afficher un message de succès
            alert(data.message);
        } else {
            // Afficher un message d'erreur
            alert(data.message || 'Une erreur est survenue lors de la conversion.');

            // Réactiver le bouton
            if (convertBtn) {
                convertBtn.textContent = originalText;
                convertBtn.disabled = false;
            }
        }
    })
    .catch(error => {
        console.error('Erreur lors de la conversion:', error);
        alert('Une erreur est survenue lors de la conversion. Veuillez réessayer.');

        // Réactiver le bouton
        if (convertBtn) {
            convertBtn.textContent = originalText;
            convertBtn.disabled = false;
        }
    });
}

/**
 * Met à jour l'historique des conversions dans l'interface
 * @param {Object} transaction - La nouvelle transaction à ajouter
 */
function updateConversionHistory(transaction) {
    console.log('Mise à jour de l\'historique avec:', transaction);
    
    // Chercher la table d'historique des conversions
    const historyTables = [
        document.querySelector('.history-table tbody'),
        document.querySelector('table tbody'),
        document.querySelector('.conversion-history tbody')
    ].filter(table => table !== null);
    
    if (historyTables.length === 0) {
        console.warn('Aucune table d\'historique trouvée dans la page');
        return;
    }
    
    // Pour chaque table d'historique trouvée
    historyTables.forEach(tableBody => {
        // Vérifier s'il y a un message "Aucune conversion"
        const emptyRow = tableBody.querySelector('tr td[colspan]');
        if (emptyRow) {
            // Supprimer la ligne "Aucune conversion"
            emptyRow.parentNode.remove();
        }
        
        // Créer une nouvelle ligne
        const newRow = document.createElement('tr');
        newRow.classList.add('new-conversion'); // Ajouter une classe pour l'animation
        
        // Formater la date
        const today = new Date();
        const formattedDate = today.toLocaleDateString('fr-FR'); // Format: DD/MM/YYYY
        
        // Formater les points avec séparateur de milliers
        const formattedPoints = parseInt(transaction.pointConvertis).toLocaleString('fr-FR');
        
        // Formater le montant avec 2 décimales et séparateur de milliers
        const formattedMontant = parseFloat(transaction.montant).toLocaleString('fr-FR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Ajouter les cellules avec les données de la transaction
        newRow.innerHTML = `
            <td>${formattedDate}</td>
            <td>${transaction.devise}</td>
            <td>${formattedPoints}</td>
            <td>${formattedMontant} ${transaction.devise}</td>
        `;
        
        // Ajouter la nouvelle ligne au début de la table
        if (tableBody.firstChild) {
            tableBody.insertBefore(newRow, tableBody.firstChild);
        } else {
            tableBody.appendChild(newRow);
        }
        
        // Ajouter une animation pour mettre en évidence la nouvelle ligne
        setTimeout(() => {
            newRow.classList.remove('new-conversion');
        }, 3000);
    });
}


// Attacher la fonction au bouton de conversion
document.addEventListener('DOMContentLoaded', function() {
    console.log('Points conversion script loaded');

    // Cibler le bouton par son ID et sa classe
    let convertButton = document.getElementById('convertBtn');
    if (!convertButton) {
        console.error('Convert button not found by ID, trying class selector');
        convertButton = document.querySelector('.convert-button');
    }

    if (convertButton) {
        console.log('Convert button found, attaching event listener');
        // Supprimer tout attribut onclick existant
        convertButton.removeAttribute('onclick');

        // Ajouter notre gestionnaire d'événement
        convertButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Convert button clicked');
            convertPoints();
        });
    } else {
        console.error('Convert button not found!');
    }

    // Remplacer la fonction window.convertPoints pour s'assurer qu'elle utilise notre implémentation
    window.convertPoints = convertPoints;

    // Ajouter un gestionnaire d'événement pour le formulaire de conversion
    const convertForm = document.querySelector('.convert-form');
    if (convertForm) {
        convertForm.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            convertPoints();
        });
    }
});
