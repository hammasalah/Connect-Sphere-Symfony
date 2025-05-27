# 💬 Connect-Sphere - Messagerie temps réel avec Symfony & Mercure
##souha jomaa
Projet Symfony permettant une messagerie en temps réel grâce au protocole **Mercure**.


## ⚙️ Prérequis

- PHP >= 8.1
- Composer
- Symfony CLI (optionnel)
- Git
- Mercure
- Navigateur web


## 🛠️ Installation du projet

### 1. Cloner le projet

```bash
git clone https://github.com/<utilisateur>/Connect-Sphere-Symfony.git
cd Connect-Sphere-Symfony

## 🛠️  Installer les dépendances Symfony
composer install

## 🛠️ Installer le bundle Mercure
composer require symfony/mercure-bundle

## 🛠️ Installer le Mercure
Télécharger le binaire
Lien : https://github.com/dunglas/mercure/releases

Télécharger : mercure_X.Y.Z_windows_amd64.exe

Renommer en : mercure.exe

Déplacer dans : /bin
###Lancer Mercure
bin/mercure.exe run -config dev.Caddyfile
