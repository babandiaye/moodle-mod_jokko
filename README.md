# Jokko — Plugin Moodle

Plugin d'activité Moodle qui intègre le protocole de messagerie Matrix (déployé sous la marque **Jokko** pour l'UNCHK). Basé sur le projet original [`matrix-org/moodle-mod_matrix`](https://github.com/matrix-org/moodle-mod_matrix) d'Element (New Vector Ltd).

> **Remarque** : le nom technique du plugin reste `mod_matrix` (component, tables DB, namespace), ce qui permet la compatibilité avec l'upstream et évite une migration structurelle. Seule l'**interface utilisateur** affiche "Jokko".

## Fonctionnalités

- Ajoute une activité Moodle qui crée un salon Matrix lié à un cours ou à un groupe
- Ajoute un champ de profil utilisateur pour l'identifiant Matrix (`@user:homeserver`)
- Synchronise automatiquement les membres des salons lors des inscriptions et modifications de groupes
- Propose automatiquement un identifiant Matrix dérivé de l'adresse email de l'utilisateur
- Deux cibles d'ouverture : `matrix.to` ou une instance Element Web configurée
- Disponible en **anglais** et en **français**

## Installation

1. Télécharger l'archive `matrix.zip` depuis la page *Releases* de ce dépôt.
2. Se connecter à Moodle en tant qu'administrateur.
3. Aller dans **Administration du site → Plugins → Installer les plugins**.
4. Déposer le fichier ZIP dans la zone d'upload et lancer l'installation.
5. Valider la mise à jour de la base de données quand Moodle la propose.

La racine du ZIP doit être un dossier nommé `matrix` (contrainte Moodle : le nom doit correspondre à `$plugin->component`).

## Configuration

Après installation : **Administration → Plugins → Modules d'activité → Jokko**.

| Paramètre | Description |
|---|---|
| **URL du homeserver** | URL du serveur Matrix (ex. `https://jokko.unchk.sn`). Sert aussi de base pour générer les identifiants utilisateurs. |
| **Jeton d'accès** | Token du bot Matrix utilisé pour créer les salons et inviter les membres. |
| **URL d'Element Web** | Instance Element personnalisée (optionnelle). Si vide, les liens ouvrent `matrix.to`. |

## Génération automatique des identifiants Matrix

Quand un utilisateur accède à une activité Jokko sans identifiant Matrix renseigné, le plugin compose automatiquement :

```
@{partie-locale-de-l-email}:{host-du-homeserver}
```

| Email | URL homeserver configurée | Identifiant généré |
|---|---|---|
| `pabn@gmail.com` | `https://jokko.unchk.sn` | `@pabn:jokko.unchk.sn` |
| `baba.ndiaye2@unchk.edu.sn` | `https://jokko.unchk.sn` | `@baba.ndiaye2:jokko.unchk.sn` |

### Méthodes d'authentification supportées

La génération automatique est active pour les utilisateurs créés via l'une des méthodes suivantes :

- `oidc` (OpenID Connect)
- `cas` (CAS)
- `manual` (création manuelle)
- `ldap` (LDAP)
- `shibboleth` (Shibboleth)

Pour les autres méthodes, l'utilisateur doit saisir son identifiant manuellement. La liste est contrôlée par la constante `SUPPORTED_AUTH_METHODS` dans [`src/Plugin/Infrastructure/MoodleFunctionBasedMatrixUserIdLoader.php`](src/Plugin/Infrastructure/MoodleFunctionBasedMatrixUserIdLoader.php).

## Propriétés des salons créés

- **Visibilité** : `private_chat` — accès uniquement sur invitation du bot
- **Historique** : `shared` — lisible par tous les membres à partir du moment où ils rejoignent
- **Accès invité** : `forbidden`
- **Chiffrement de bout en bout** : **désactivé par défaut** pour éviter les problèmes de déchiffrement lors des invitations tardives et la friction liée à la validation des sessions. Le chiffrement pourra être réintroduit ultérieurement via une option dédiée.

## Différences par rapport au projet upstream

Cette version apporte les changements suivants par rapport à [`matrix-org/moodle-mod_matrix`](https://github.com/matrix-org/moodle-mod_matrix) :

| Domaine | Changement |
|---|---|
| Branding | Libellés "Matrix" remplacés par "Jokko" dans toute l'UI (plugin name, activité, messages, erreurs, aide). |
| Langues | Ajout de la traduction française complète ([`lang/fr/matrix.php`](lang/fr/matrix.php)). |
| Génération auto | Extraction du `localpart` à partir de l'email (au lieu du username) + support multi-auth. |
| Configuration | Le host du homeserver est dérivé de la configuration admin — plus de valeur codée en dur. |
| Sécurité salons | Chiffrement E2E désactivé (avec `private_chat` conservé). |
| Correctifs | `return` manquant dans `ListRoomsAction`, type de paramètre de `groups_get_activity_allowed_groups`. |

## Pré-requis

- Moodle **3.9 LTS** ou supérieur
- PHP **7.4+** (testé jusqu'à PHP 8.1)
- Extensions PHP : `curl`, `json`
- Un homeserver Matrix accessible et un compte bot avec un token d'accès

## Licence

Apache-2.0 — voir [`LICENSE`](LICENSE).

## Crédits

- **Projet original** : [matrix-org/moodle-mod_matrix](https://github.com/matrix-org/moodle-mod_matrix) — New Vector Ltd (Element) et Andreas Möller.
- **Adaptation Jokko / UNCHK** : Université Numérique Cheikh Hamidou Kane.

## Développement

Cette version déployable ne contient que les fichiers nécessaires au fonctionnement (code, `vendor/` en mode production, langues, backup, icônes). Pour contribuer ou exécuter les tests, se référer à l'environnement de développement qui conserve `composer.json`, la suite de tests PHPUnit, la configuration Psalm et les conteneurs Docker fournis par l'upstream.
