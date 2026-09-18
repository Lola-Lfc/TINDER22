# Blogart Template

## Cooker — inscription et connexion

L’accueil `index.php` affiche l’inscription aux visiteurs et redirige les
utilisateurs connectés vers `/discover`. Le lien « Mon profil » ouvre `/user/ID`. Cette route affiche leur profil privé.
Le lien de modification ouvre `/user/ID?edit=1` et réutilise
`views/backend/users/edit.php`. `api/users/update.php` valide les informations
et utilise uniquement l’identifiant de session pour la mise à jour. La photo
actuelle est conservée lorsqu’aucune nouvelle photo n’est envoyée. Les formulaires sont dans les fichiers
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

## Routes de profil et serveur local

Pour respecter la contrainte de ne créer aucun fichier, le routage Apache est
configuré dans le fichier existant `/Applications/MAMP/conf/apache/httpd.conf` :
`FallbackResource /index.php` dans le bloc Directory du projet. MAMP a été
redémarré pour prendre en compte cette configuration. Sur un autre serveur,
il faut reporter cette directive et adapter son chemin si le projet est dans
un sous-dossier. Aucun fichier `.htaccess` n’est ajouté.

Le schéma actuel utilise utf8mb3 : les caractères Unicode sur quatre octets,
notamment certains emojis, sont refusés avec une erreur de validation. La
structure et l’encodage de la base restent inchangés.

Le profil, les modifications et les photos ont été testés par HTTP sur MAMP,
y compris les erreurs de validation, le refus d’un autre identifiant, la
conservation de session après modification et le refus après déconnexion.

## Découverte des profils

`/discover` réutilise `index.php` et `views/backend/likes/list.php`. Une requête
récupère un seul autre utilisateur et exclut le compte connecté ainsi que
tous les profils déjà traités par ce compte. Seuls le prénom, l’âge, le genre,
la photo et la biographie sont présentés.

Les boutons Pass et Like envoient un POST avec CSRF à `api/likes/create.php`.
La table existante LIKES conserve `likeL1 = 0` pour Pass et `likeL1 = 1` pour
Like. L’auteur provient exclusivement de la session. La clé primaire de
la table rend les soumissions répétées sans effet sur le choix initial.
Après le POST, une redirection affiche le profil suivant. Un état vide est
affiché lorsque tous les autres profils ont été traités.

Vérifications effectuées sans créer de fichier de test : exclusion de son
propre compte, affichage unique, enregistrement Pass et Like, CSRF,
paramètres invalides, répétition de soumission, état vide, arrivée d’un
nouvel utilisateur, persistance après reconnexion et rendu mobile à 390 px.
