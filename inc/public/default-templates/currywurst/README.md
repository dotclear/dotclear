# Currywurst

Un nouveau jeu de templates par défaut pour Dotclear (versions ≥ 2.7)

## Motivations

Le jeu de templates Currywurst répond à divers objectifs :

- Proposer un jeu de templates en HTML 5.
- Pouvoir ainsi y introduire des balises [http://fr.wikipedia.org/wiki/Accessible_Rich_Internet_Applications](ARIA) pour une meilleure accessibilité.
- Améliorer la sémantique des tags html (par exemple ne plus présenter les commentaires dans une liste de définition).
- Factoriser les éléments récurrents dès que c'est possible sans pâtir à la compréhension.
- Préparer le passage à la syntaxe [http://twig.sensiolabs.org/](twig).
- Adopter une nomenclature inspirée de [http://csswizardry.com/2013/01/mindbemding-getting-your-head-round-bem-syntax/](BEM) – sans en faire une religion dogmatique toutefois. Voir aussi [http://coding.smashingmagazine.com/2011/12/12/an-introduction-to-object-oriented-css-oocss/](OOCSS).
- Faire en sorte de réduire l'obligation de recourir aux sélecteurs enfants (`#top h1 {}` par exemple) ou aux ID dans les CSS.

## Revue fichier par fichier

Ne sont pas listées les modifications systématiques : ajout de classes sur tous les éléments « probablement stylés » ou ne possédant qu'un id ; syntaxe html5.

Les modifications nécessitant des explications sont listées de la façon suivante :

    <Description courte> : <motivation>. <Explication>.

Les motivations sont abrégées en :
- a11y : amélioration accessibilité
- sém. : amélioration ou correction sémantique (html)
- cont. : amélioration du contenu
- ergo : amélioration ergonomique

Par convention l'ordre des attributs dans les balises html est *class, id, autres attributs*.

Toutes les listes (ul ou ol) comportent le terme "list" dans leur nom de class.

### _top.html

Déplacement des liens d'accès rapides au-dessus du titre du blog : **a11y**, **ergo**. *Le titre de fenêtre étant lu avant tout par les aides techniques, l'internaute sait déjà où il se trouve. L'accès est également plus rapide au clavier. Côté design, la plupart des créateurs de thèmes ont pris l'habitude de déplacer ce bloc en haut de la page ; ils n'auront plus besoin de passer par des position absolute pour ce faire.*

### _simple-entry.html

(aka contexte du billet seul entier avec commentaires etc.)

Formulaire de dépôt de commentaire :
- remplacement des inputs par des buttons
- typage des champs
- ajout des attributs required quand nécessaire

Découpage en deux grandes div : post et post-feedback.
- post
  - post-title : titre
  - post-meta
    - post-info
    - post-tags-list
  - post-excerpt
  - post-content
  - post-attachments
    - post-attachments-title
    - post-attachments-list
- post-feedback
  - feedback__comments
    - comments-feed
    - comments-list
  - comment-form
  - send-trackback

### __layout.html

Reprend l'intégralité du home.html et définit les blocs permettant l'héritage et extension

La liste des noms utilisés pour les blocs sont les suivants (les noms sont composés de deux termes, le nom du parent direct du bloc et le nom du bloc en question, sachant que le nom 'attr' est réservé aux attributs des balises, comme par exemple 'body-attr', et 'tag' pour encadrer une balise ouvrante seule) :

- html-head : contenu de la balise head
  - head-title : balise title
  - head-meta : liste des balises meta du head
    - meta-robots : consigne pour les robots
    - meta-entry : partie spécifique au contexte
  - head-dc : liste des balises dublin core du head
    - dc-entry : partie spécifique au contexte
  - head-linkrel : liste des balises link rel du head
- body-tag : balise body (sans contenu ni balise fermante)
- html-body : contenu de la balise body
  - body-page : contenu de la div #page
    - page-top : contenu d'entête de la div #page (en général inclusion du _top.html)
    - page-wrapper : contenu principal de la div #page, soit le contenu de la div #wrapper
      - wrapper-main : contenu principal de la div #wrapper, soit le contenu de la div #main
        - main-content : contenu de la div #content
      - wrapper-sidebar : contenu annexe de la div #wrapper (en général inclusion de _sidebar.html)
    - page-footer : contenu de pied de page de la div #page (en général inclusion du _footer.html)

Pour rappel, la hiérarchie des blocs html est la suivante :

- html
  - head
  - body
    - #page
      - .header
      - #wrapper
        - #main
          - #content
        - #sidebar/.sidebar
      - #footer/.footer


***

A réfléchir : inclusion par défaut de liens "sociaux" ?

***
