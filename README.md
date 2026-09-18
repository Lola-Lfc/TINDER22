# Blogart Template

## Cooker — inscription et connexion

L’accueil `index.php` affiche l’inscription aux visiteurs et les informations
du compte aux utilisateurs connectés. Les formulaires sont dans les fichiers
existants `views/backend/security/signup.php` et `login.php`. Les API existantes
`api/security/signup.php`, `login.php` et `disconnect.php` traitent les requêtes.

Les fonctions communes sont dans `functions/security.php`, le démarrage de
session dans `config/security.php`, les styles dans `src/css/style.css` et le
JavaScript directement dans le formulaire d’inscription. Les visuels Figma
sont intégrés dans `functions/data.php`, sans fichier image supplémentaire.

Prérequis : PHP avec PDO MySQL, mbstring, fileinfo et GD ; configuration `.env` ;
dossier existant `src/images` accessible en écriture pour les photos utilisateurs.
Ces photos sont des données générées à l’exécution, exclues de Git, facultatives,
limitées à 5 Mo et 4096 × 4096 pixels, puis réencodées en JPEG. Les photos déjà
enregistrées dans `src/uploads` restent accessibles.

La base doit contenir les colonnes du fichier `BDD/CreateDbTinder22.sql` et les
choix de genre. Aucune modification automatique de structure n’est exécutée.
Une connexion réussie renouvelle l’identifiant de session et conserve `USER_ID`.
La déconnexion fonctionne en POST avec un jeton CSRF et détruit la session.
Les pages d’authentification et du compte sont servies sans cache.

L’inscription, les doublons d’email, la validation des photos, la connexion,
le renouvellement de session et la déconnexion ont été vérifiés par HTTP et en
base. Les tests ont été exécutés sans conserver de nouveau fichier de test.

## Setup


## Architecture
- **api** - Contains all php calls for example "create.php" for statuts, articles
- **classes** - Contains all classes for example "members.php"
- **config** - Contains all the configuration files specific to the operation of the application, for example "security.php"
- **functions** - Contains all the functions of your code for example "data.php", "create.php"
- **views** - Contain all front
- **src** - Contain all sources files or external libs

## Files to complete
- **.env** - Foreach user exemple in .env.example
- **config/security.php** - Check user cookie
- **index.php** - Must be the homepage
- **views** - All your pages
-
