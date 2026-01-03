# Brewers Social Club - Brew Manager

## 🇫🇷 Installation (Français)

**Important :** Ce dépôt contient le code source. Si vous téléchargez le code depuis GitHub, vous obtiendrez un fichier `.zip` contenant tout le dépôt. **Ce fichier ne peut pas être installé directement sur WordPress** car le plugin se trouve dans un sous-dossier.

**Pour installer correctement le plugin :**

1. **Décompressez** l'archive du dépôt que vous avez téléchargée.
2. Localisez le dossier nommé `bsc-brew-manager`.
3. **Compressez (zippez)** uniquement ce dossier `bsc-brew-manager`.
   - *Sur Windows :* Clic droit sur le dossier > Envoyer vers > Dossier compressé.
   - *Sur Mac :* Clic droit sur le dossier > Compresser "bsc-brew-manager".
4. Sur votre site WordPress, allez dans **Extensions > Ajouter > Téléverser une extension**.
5. Choisissez le fichier `bsc-brew-manager.zip` que vous venez de créer.
6. Cliquez sur **Installer maintenant** puis **Activer**.

### Dépannage
**Erreur : "L’archive n’a pas pu être installée. Aucune extension valide trouvée."**
Cela signifie que vous avez probablement zippé le dossier racine du dépôt (qui contient le dossier `bsc-brew-manager`) au lieu de zipper le dossier du plugin lui-même. WordPress ne cherche pas les plugins dans les sous-dossiers. Assurez-vous que votre fichier zip contient directement `bsc-brew-manager.php` à l'intérieur du dossier compressé (structure: `votre-zip.zip > bsc-brew-manager > bsc-brew-manager.php`).

## 🇬🇧 Installation (English)

**Important:** This repository contains the source code. Downloading the repository zip from GitHub will give you a file containing the root folder. **This file cannot be installed directly on WordPress** because the plugin resides in a subfolder.

**To install correctly:**

1. **Unzip** the downloaded repository archive.
2. Locate the folder named `bsc-brew-manager`.
3. **Zip** only this `bsc-brew-manager` folder.
4. In your WordPress Admin, go to **Plugins > Add New > Upload Plugin**.
5. Select the `bsc-brew-manager.zip` file you created.
6. Click **Install Now** and then **Activate**.

### Troubleshooting
**Error: "The package could not be installed. No valid plugins were found."**
This means you likely zipped the repository root (containing `bsc-brew-manager` folder) instead of the plugin folder itself. WordPress does not scan subdirectories for plugins. Ensure your zip file contains `bsc-brew-manager.php` inside the compressed folder (structure: `your-zip.zip > bsc-brew-manager > bsc-brew-manager.php`).

---

## Fonctionnalités / Features

- **Gestion des Brasseries & Bières :** Créez et gérez vos entités directement depuis WordPress.
- **Synchronisation Supabase :** Connecté à la base de données BeerBeerAndBeer (`breweries`, `beers`) avec mappage des champs spécifiques.
- **Recipe Builder (Constructeur de Recette) :** Interface visuelle "Timeline" pour créer des recettes étape par étape (Empâtage, Ébullition, etc.).
- **Gestion Avancée des Ingrédients :**
  - **Malts :** Liste déroulante catégorisée, champs pour EBC, Rendement, Fournisseur.
  - **Houblons :** Liste catégorisée (Amérisants, Aromatiques...), champs pour Acide Alpha, Forme, Origine, et Tags Aromatiques.
- **Intégration Elementor :** Widgets dédiés pour l'affichage des infos brasserie/bière, formulaires, et listes.
- **Système de Notation & Dégustation :** Synchronisation des notes et formulaire de demande de dégustation.
