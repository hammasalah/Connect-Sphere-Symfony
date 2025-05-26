<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\Roulette;
use App\Entity\HistoriquePoints;
use Doctrine\ORM\EntityManagerInterface;

class RouletteService
{
    private const MAX_DAILY_SPINS = 4;
    private const REWARDS = [0, 100, 50, 40, 200];

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function canSpin(Users $user): bool
    {
        // Utiliser la date et heure actuelles pour plus de précision
        $now = new \DateTime();
        $today = new \DateTime($now->format('Y-m-d') . ' 00:00:00');
        
        try {
            // Récupérer les tours effectués aujourd'hui avec des dates précises
            $startOfDay = clone $today;
            $endOfDay = (clone $today)->modify('+1 day');
            
            // Log détaillé des paramètres de date pour déboguer
            error_log('DEBUG - Période de recherche: ' . $startOfDay->format('Y-m-d H:i:s') . ' à ' . $endOfDay->format('Y-m-d H:i:s'));
            error_log('DEBUG - Date actuelle: ' . $now->format('Y-m-d H:i:s'));
            
            // Utiliser une requête DQL avec des paramètres explicites
            $spinCount = $this->entityManager->getRepository(Roulette::class)
                ->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.user = :user')
                ->andWhere('r.createdAt >= :startOfDay')
                ->andWhere('r.createdAt < :endOfDay')
                ->setParameter('user', $user)
                ->setParameter('startOfDay', $startOfDay, '\Doctrine\DBAL\Types\DateTimeType')
                ->setParameter('endOfDay', $endOfDay, '\Doctrine\DBAL\Types\DateTimeType')
                ->getQuery()
                ->getSingleScalarResult();
                
            // Assurons-nous que spinCount est bien un entier
            $spinCount = (int)$spinCount;
            
            // Log détaillé pour déboguer
            error_log('DEBUG - Utilisateur ID: ' . $user->getId() . ', Nombre de tours aujourd\'hui: ' . $spinCount . ' (max: ' . self::MAX_DAILY_SPINS . ')');
            
            // Vérifier si l'utilisateur a au moins un tour disponible
            $canSpin = $spinCount < self::MAX_DAILY_SPINS;
            error_log('DEBUG - L\'utilisateur peut-il tourner la roue? ' . ($canSpin ? 'OUI' : 'NON'));
            
            return $canSpin;
        } catch (\Exception $e) {
            // En cas d'erreur, permettre à l'utilisateur de tourner la roue mais logger l'erreur
            error_log('ERREUR - Vérification des tours: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return true;
        }
    }

    public function getRemainingSpins(Users $user): int
    {
        // Utiliser la date et heure actuelles pour plus de précision
        $now = new \DateTime();
        $today = new \DateTime($now->format('Y-m-d') . ' 00:00:00');
        
        try {
            // Récupérer les tours effectués aujourd'hui avec des dates précises
            $startOfDay = clone $today;
            $endOfDay = (clone $today)->modify('+1 day');
            
            // Log détaillé des paramètres de date pour déboguer
            error_log('DEBUG - Calcul des tours restants - Période: ' . $startOfDay->format('Y-m-d H:i:s') . ' à ' . $endOfDay->format('Y-m-d H:i:s'));
            error_log('DEBUG - Date actuelle: ' . $now->format('Y-m-d H:i:s'));
            
            // Utiliser une requête DQL avec des paramètres explicites
            $spinCount = $this->entityManager->getRepository(Roulette::class)
                ->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.user = :user')
                ->andWhere('r.createdAt >= :startOfDay')
                ->andWhere('r.createdAt < :endOfDay')
                ->setParameter('user', $user)
                ->setParameter('startOfDay', $startOfDay, '\Doctrine\DBAL\Types\DateTimeType')
                ->setParameter('endOfDay', $endOfDay, '\Doctrine\DBAL\Types\DateTimeType')
                ->getQuery()
                ->getSingleScalarResult();
            
            // Assurons-nous que spinCount est bien un entier
            $spinCount = (int)$spinCount;
            
            // Calculer les tours restants
            $remainingSpins = max(0, self::MAX_DAILY_SPINS - $spinCount);
            
            // Log détaillé pour déboguer
            error_log('DEBUG - Utilisateur ID: ' . $user->getId() . ', Tours effectués: ' . $spinCount . ', Tours restants: ' . $remainingSpins);
            
            return $remainingSpins;
        } catch (\Exception $e) {
            // En cas d'erreur, indiquer que l'utilisateur a tous ses tours disponibles mais logger l'erreur
            error_log('ERREUR - Calcul des tours restants: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return self::MAX_DAILY_SPINS;
        }
    }

    public function getNextSpinDate(): \DateTime
    {
        // Retourne le jour suivant puisque la roue peut être utilisée tous les jours
        $today = new \DateTime('today');
        return (clone $today)->modify('+1 day');
    }

    public function spin(Users $user): array
    {
        // Vérifier si l'utilisateur peut tourner la roue avec logs détaillés
        $remainingSpins = $this->getRemainingSpins($user);
        error_log('DEBUG - Spin demandé - Utilisateur ID: ' . $user->getId() . ', Tours restants: ' . $remainingSpins);
        
        if (!$this->canSpin($user)) {
            error_log('ALERTE - Tentative de tour de roue refusée pour l\'utilisateur ID: ' . $user->getId() . ', Tours restants: ' . $remainingSpins);
            throw new \Exception('Vous avez atteint le nombre maximum de tours pour aujourd\'hui');
        }

        error_log('DEBUG - Tour de roue autorisé pour l\'utilisateur ID: ' . $user->getId());
        $reward = self::REWARDS[array_rand(self::REWARDS)];
        error_log('DEBUG - Récompense tirée: ' . $reward . ' points');
        
        // Valider les données
        $validationResult = $this->validateRouletteData($user, $reward);
        if (!$validationResult) {
            error_log('Validation des données échouée pour l\'utilisateur ID: ' . $user->getId());
            throw new \Exception('Données de la roulette invalides.');
        }

        // Ajouter les points
        $currentPoints = $user->getPoints() ?? 0;
        $user->setPoints($currentPoints + $reward);
        error_log('Points mis à jour: ' . $currentPoints . ' + ' . $reward . ' = ' . $user->getPoints());

        // Enregistrer le tour avec date précise
        $rouletteSpin = new Roulette();
        $rouletteSpin->setUser($user);
        // Définir le résultat (l'entité Roulette utilise setResult)
        $rouletteSpin->setResult($reward . ' points');
        
        // Définir la date actuelle avec précision (l'entité Roulette utilise setCreatedAt)
        $now = new \DateTime();
        $rouletteSpin->setCreatedAt($now);
        error_log('DEBUG - Enregistrement du tour à la date exacte: ' . $now->format('Y-m-d H:i:s'));
        error_log('DEBUG - Objet date créé: ' . get_class($now) . ', Timezone: ' . $now->getTimezone()->getName());

        // Enregistrer l'historique
        $historiquePoints = new HistoriquePoints();
        $historiquePoints->setUser($user);
        $historiquePoints->setType('gain');
        $historiquePoints->setPoints($reward);
        $historiquePoints->setRaison('roulette');
        $historiquePoints->setDate($now);

        try {
            // Persister les entités avec gestion d'erreur
            $this->entityManager->persist($user);
            $this->entityManager->persist($rouletteSpin);
            $this->entityManager->persist($historiquePoints);
            $this->entityManager->flush();
            error_log('DEBUG - Données persistées avec succès dans la base de données');
            
            // Recalculer les tours restants après l'enregistrement
            $remainingSpins = $this->getRemainingSpins($user);
            error_log('DEBUG - Tours restants après ce tour: ' . $remainingSpins);
        } catch (\Exception $e) {
            error_log('ERREUR - Échec de persistance des données: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            throw new \Exception('Erreur lors de l\'enregistrement des points. Veuillez réessayer.');
        }
        
        return [
            'reward' => $reward,
            'remainingSpins' => $remainingSpins,
            'totalPoints' => $user->getPoints(),
            'pointsWon' => $reward
        ];
    }

    /**
     * Valide les données de la roulette avant de les persister
     * 
     * @param Users $user L'utilisateur qui tourne la roue
     * @param int $pointsWon Les points gagnés
     * @return bool Retourne true si les données sont valides
     * @throws \Exception Si les données ne sont pas valides
     */
    public function validateRouletteData(Users $user, int $pointsWon): bool
    {
        try {
            // Vérifier que l'utilisateur existe et a un ID
            if (!$user || !$user->getId()) {
                throw new \Exception('Utilisateur invalide ou ID manquant');
            }
            
            // Vérifier que les points sont valides
            if (!is_int($pointsWon)) {
                // Tenter de convertir en entier si ce n'est pas déjà le cas
                $pointsWon = (int)$pointsWon;
                error_log('Points convertis en entier: ' . $pointsWon);
            }
            
            if ($pointsWon < 0) {
                throw new \Exception('Valeur de points négative: ' . $pointsWon);
            }
            
            // Vérifier que l'utilisateur a les méthodes nécessaires
            if (!method_exists($user, 'getPoints') || !method_exists($user, 'setPoints')) {
                throw new \Exception('Les méthodes getPoints ou setPoints ne sont pas définies pour l\'utilisateur');
            }
            
            // Vérifier que la somme ne dépasse pas la capacité d'un entier
            $currentPoints = $user->getPoints() ?? 0;
            if ($currentPoints > PHP_INT_MAX - $pointsWon) {
                throw new \Exception('Dépassement de la valeur maximale de points');
            }
            
            // Vérifier si la valeur des points est dans la liste des récompenses autorisées
            if (!in_array($pointsWon, self::REWARDS)) {
                error_log('Avertissement: Valeur de points non standard: ' . $pointsWon . ', mais traitement autorisé');
            }
            
            return true;
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas la propager
            error_log('Erreur de validation dans RouletteService: ' . $e->getMessage());
            // Retourner false au lieu de lancer une exception
            return false;
        }
    }
    
    public function getRewardInfo(int $reward): array
    {
        return [
            'reward' => $reward,
            'totalPoints' => 0,
            'pointsWon' => $reward
        ];
    }


    public function getSpinHistory(Users $user, int $limit = 5): array
    {
        return $this->entityManager->getRepository(Roulette::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}