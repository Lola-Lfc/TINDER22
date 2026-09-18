# Blogart Template

## Cooker — inscription et connexion

L’accueil `index.php` présente Cooker et les trois étapes du concept. Les visiteurs
peuvent accéder à l’inscription et à la connexion ; les utilisateurs connectés
peuvent ouvrir la découverte et leurs matchs. Le lien « Mon profil » ouvre `index.php?user=ID`. Cette route affiche leur profil privé.
Le lien de modification ouvre `index.php?user=ID&edit=1` et réutilise
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

## Installation sur chaque ordinateur (macOS ou Windows)

GitHub partage les fichiers du projet. Chaque ordinateur doit disposer de son
propre serveur PHP/MySQL, de sa base, de son fichier `.env` et de son routage
Apache. Le fichier `.env` est volontairement exclu de Git.

1. Récupérer la dernière version du dépôt et démarrer PHP/Apache et MySQL.
2. Pour une base neuve, importer `BDD/CreateDbTinder22.sql` dans phpMyAdmin.
   Le fichier crée `TINDER22`, les tables et les quatre choix de genre.
   Ne pas réimporter tout le fichier sur une base qui contient déjà les tables.
3. Si la base est déjà importée mais que le genre est vide, sélectionner cette
   base dans phpMyAdmin et exécuter uniquement le bloc `INSERT INTO GENRE`
   situé à la fin du SQL. Il ajoute uniquement les choix manquants.
4. Copier `.env.example` en `.env` sur chaque ordinateur et adapter `DB_HOST`,
   `DB_PORT`, `DB_USER`, `DB_PASSWORD` et `DB_DATABASE` aux réglages MySQL locaux.
   Le nom de la base du SQL est exactement `TINDER22`. Pour forcer une connexion
   TCP utilisant le port indiqué, utiliser `DB_HOST=127.0.0.1` ; `localhost`
   peut utiliser un socket sur macOS. Si MySQL n’a pas de mot de passe,
   renseigner `DB_PASSWORD=`. Ne pas pousser `.env` sur GitHub.
5. Activer PDO MySQL, mbstring, fileinfo et GD dans le PHP utilisé par le serveur.
6. Les pages utilisent des paramètres GET (`index.php?user=ID` et
   `index.php?page=discover`). Aucune configuration de routage Apache n’est
   nécessaire, que le projet soit à la racine ou dans un sous-dossier.
7. Ouvrir le projet par HTTP, vérifier les quatre choix de genre, puis tester
   l’inscription et la connexion. Les photos et les comptes de l’ordinateur
   d’un autre collaborateur ne sont pas automatiquement partagés.

Si l’inscription est indisponible, vérifier dans phpMyAdmin :

```sql
SELECT idGenr, libGenr FROM GENRE;
SHOW COLUMNS FROM USER;
```

GENRE doit contenir les choix, et USER doit contenir `emailUser` et
`passwordUser`. Le journal d’erreurs PHP/Apache distingue une table GENRE
vide, des colonnes manquantes et une erreur de connexion SQL. Le code
n’ajoute pas automatiquement de colonnes ou de base lors d’une visite.


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

## Navigation portable

Le profil utilise `index.php?user=ID`, sa modification
`index.php?user=ID&edit=1`, et la découverte `index.php?page=discover`.
Les liens sont générés avec le préfixe du projet pour fonctionner à la racine
et dans un sous-dossier, sur macOS et Windows. Les formulaires de modification,
d’inscription, de connexion, de déconnexion, de Pass et de Like utilisent POST.
Aucun fichier `.htaccess` ou réglage Apache supplémentaire n’est nécessaire.

Le schéma actuel utilise utf8mb3 : les caractères Unicode sur quatre octets,
notamment certains emojis, sont refusés avec une erreur de validation. La
structure et l’encodage de la base restent inchangés.

Le profil, les modifications et les photos ont été testés par HTTP sur MAMP,
y compris les erreurs de validation, le refus d’un autre identifiant, la
conservation de session après modification et le refus après déconnexion.

## Découverte des profils

`index.php?page=discover` réutilise `index.php` et `views/backend/likes/list.php`. Une requête
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

## Match réciproque

Après un Like, l’API vérifie les deux lignes LIKES avec `likeL1 = 1`.
Elle crée une seule paire dans MATCHS, avec le plus petit identifiant en
premier, et vérifie aussi les éventuelles paires déjà stockées dans l’autre
sens. Un verrou MySQL par paire sérialise les Likes simultanés ; la décision
et le match sont enregistrés dans une même transaction.

Un nouveau match est placé dans le message temporaire de session de la
découverte. Après redirection, une popup « C’est un match ! » affiche les
deux photos et le prénom du binôme. Son bouton permet de poursuivre la
découverte. Le message est consommé une seule fois.

Vérifications : Like simple, Like réciproque, Pass, absence de doublons et de
popup répétée, apparition et fermeture du dialogue dans le navigateur,
format mobile à 390 px. Aucun fichier ou changement de schéma ajouté.

## Mes matchs

`index.php?page=matches` affiche les matchs de la session connectée via le
fichier existant `views/backend/matchs/list.php`. La requête récupère les
utilisateurs liés au compte dans les deux sens de MATCHS et affiche une
seule carte par personne, avec photo, prénom, âge, genre et biographie.
Les emails et mots de passe ne sont pas récupérés par cette requête.

Le menu et la popup de nouveau match proposent un lien vers cette page.
Un état vide invite à découvrir des profils lorsqu’aucun match n’existe.
La grille affiche trois colonnes sur ordinateur, deux sur tablette et une
sur mobile. L’accès nécessite une session valide.

Tests : état vide, deux sens des paires, absence de doublons, exclusion des
matchs d’autres comptes, échappement du texte, accès après déconnexion,
racine et sous-dossier, contrôle visuel sur ordinateur et mobile à 390 px.

## Profil d’un match et Unmatch

Les cartes de Mes matchs proposent « Voir le profil » vers `index.php?user=ID`.
Tout utilisateur connecté peut consulter les autres profils, même sans match.
Le bouton « Voir le profil » est aussi présent sur les cartes de découverte. Il affiche uniquement le prénom, l’âge, le genre,
la photo et la biographie. Son édition est refusée. Le bouton Unmatch apparaît uniquement si un match existe.

Le bouton Unmatch envoie un POST avec CSRF à l’API existante
`api/matchs/delete.php`. Le compte auteur vient uniquement de la session.
La suppression concerne les deux orientations possibles de la paire dans
MATCHS et utilise le même verrou MySQL que la création de match. Elle ne
modifie pas LIKES. Après redirection vers Mes matchs, la carte disparaît et
un message confirme la suppression.

La création de match après Like est limitée à une nouvelle décision insérée :
une ancienne soumission répétée ne recrée pas un match supprimé.

Tests effectués : consultation d’un match, confidentialité des informations,
édition refusée, CSRF, suppression des deux sens, conservation des autres
paires et des Likes, répétitions de soumission, suppression du bouton Unmatch après rupture, et accès refusé après déconnexion. Aucun fichier ni modification de structure ajouté.


## Administration

Le lien « Admin » ouvre `index.php?page=admin` uniquement pour le compte
connecté dont l’identifiant est **4**. Le contrôle est également exécuté sur
chaque API d’administration et sur les anciennes vues, même en accès direct.
Les formulaires utilisent POST avec un jeton CSRF spécifique.

Le panneau permet de consulter, créer, modifier et supprimer les utilisateurs,
les likes/pass, ainsi que consulter, créer et
supprimer les matchs. Les deux utilisateurs d’une relation restent fixes :
pour changer une paire, la supprimer puis en créer une autre. Les likes
réciproques positifs créent un match. Les mots de passe ne sont jamais affichés ;
un mot de passe laissé vide lors d’une modification reste inchangé.

Le compte #4 ne peut pas être supprimé. La suppression d’un utilisateur
supprime également ses relations via les contraintes existantes de la base.
Les panneaux Genres et Commentaires ont été retirés, avec refus d’accès direct
aux vues et aux API correspondantes. Les genres restent disponibles pour
l’inscription et les profils. La liste est paginée
par blocs de 100 éléments. Les API `statutsCC` sont désactivées : la base
Cooker ne contient pas de table `STATUT`. Aucune table n’est ajoutée.

Les IDs utilisateurs ne représentent pas un compteur de comptes. Le réglage
MySQL local `auto_increment_increment` vaut 1. Les comptes temporaires créés
puis supprimés lors des vérifications précédentes ont consommé des IDs,
ce qui explique une partie des trous entre 4, 18 et 27. Les insertions échouées
ou annulées peuvent aussi laisser des trous. Les IDs existants sont conservés.
