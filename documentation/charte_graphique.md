# Charte Graphique - Vite & Gourmand

Version: 2026-04-27  
Projet: Application web Vite & Gourmand

## 0. Perimetre de reference (important)

Cette charte s'appuie prioritairement sur les maquettes (wireframes + mockups), qui font foi pour le livrable de conception ECF.

Les maquettes ont ete produites a la main pour cadrer les parcours, puis l'interface finale a ete amelioree en phase d'integration (lisibilite, responsive, accessibilite).

L'UI implementee dans l'application sert de reference secondaire pour:
- verifier la faisabilite,
- mesurer les ecarts,
- documenter les ajustements de realisation.

## 1. Intention visuelle

L'identite visuelle vise a transmettre:
- convivialite,
- professionnalisme artisanal,
- lisibilite des informations de commande.

Le design privilegie une interface claire, accessible et rassurante pour des utilisateurs non techniques.

## 2. Palette de couleurs

Palette principale (a ajuster selon maquettes finales):

- Couleur primaire (actions, liens principaux): `#1F6F5F`
- Couleur secondaire (accents): `#D97706`
- Couleur fond clair: `#F8F5F0`
- Couleur surface (cartes): `#FFFFFF`
- Couleur texte principal: `#1F2937`
- Couleur texte secondaire: `#6B7280`
- Couleur succes: `#15803D`
- Couleur alerte: `#B91C1C`

Regles d'usage:
- Garder un contraste eleve texte/fond.
- Utiliser la primaire pour les actions principales (CTA).
- Utiliser la secondaire pour les accents ou badges.
- Limiter les couleurs d'etat (succes/alerte) aux messages systeme.

## 3. Typographie

Recommandation:
- Titres: `Poppins` (ou `Montserrat`)
- Texte courant: `Source Sans 3` (ou `Open Sans`)

Echelle typographique:
- H1: 32 px / 700
- H2: 24 px / 600
- H3: 20 px / 600
- Texte courant: 16 px / 400
- Legendes: 14 px / 400

Regles:
- Interlignage confortable (1.4 a 1.6).
- Longueur de ligne maitrisee sur desktop.
- Taille minimale lisible sur mobile.

## 4. Grille et layout

- Desktop: grille 12 colonnes.
- Tablet: grille 8 colonnes.
- Mobile: 4 colonnes.
- Marges externes harmonisees (desktop > tablet > mobile).
- Espacement base sur une echelle coherente (ex: 4/8/12/16/24/32 px).

## 5. Composants UI

### 5.1 Boutons
- Primaire: fond couleur primaire + texte blanc.
- Secondaire: fond clair + bordure primaire + texte primaire.
- Etat hover: contraste renforce.
- Etat disabled: opacite reduite + curseur interdit.

### 5.2 Cartes menu
- Titre, description, prix min, personnes min, CTA detail.
- Image principale visible.
- Hierarchie visuelle stable sur toutes les resolutions.

### 5.3 Formulaires
- Labels explicites au-dessus des champs.
- Messages d'erreur visibles et comprehensibles.
- Indication des champs obligatoires.
- Focus clavier visible.

### 5.4 Navigation
- Menu principal simple: accueil, menus, contact, connexion/profil.
- Acces contextuel selon role (user/employe/admin).

## 6. Iconographie et imagerie

- Icones simples et coherentes (style lineaire ou plein, mais uniforme).
- Images de menus qualitatives, ratio coherent.
- Alt text systematique pour l'accessibilite.

## 7. Accessibilite (RGAA - principes)

- Contraste conforme pour textes et composants.
- Navigation clavier sur tous les parcours.
- Labels de formulaires explicites.
- Structure semantique (titres, listes, zones de navigation).
- Textes d'etat comprehensibles sans ambiguite.

## 8. Ton editorial

- Ton clair, professionnel et cordial.
- Phrases courtes sur les pages critiques (commande, validation).
- Vocabulaire metier compréhensible grand public.

## 9. Coherence multi-supports

- Priorite mobile-first sur la lisibilite.
- Adaptation desktop sans rupture visuelle.
- Composants et couleurs identiques entre maquettes desktop/mobile.
