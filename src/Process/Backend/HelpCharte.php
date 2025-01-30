<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;

class HelpCharte extends Process
{
    /**
     * Initializes the page.
     */
    public static function init(): bool
    {
        Page::check(
            App::auth()->makePermissions([
                App::auth()::PERMISSION_USAGE,
                App::auth()::PERMISSION_CONTENT_ADMIN,
            ])
        );

        App::backend()->data_theme = App::auth()->prefs()->interface->theme;
        App::backend()->js         = [
            'htmlFontSize' => App::auth()->prefs()->interface->htmlfontsize,
            'debug'        => App::config()->debugMode(),
        ];

        return self::status(true);
    }

    /**
     * Gets the theme.
     *
     * @return     string  The theme.
     */
    public static function getTheme(): string
    {
        return App::backend()->data_theme ?? '';
    }

    /**
     * Gets the JS variables.
     *
     * @return     array<string, string>  The js.
     */
    public static function getJS(): array
    {
        return App::backend()->js ?? [];
    }

    /**
     * Renders the page.
     */
    public static function render(): void
    {
        ?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= self::getTheme() ?>">
<!-- included by ../_charte.php -->
<head>
  <meta charset="UTF-8">
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW">
  <meta name="GOOGLEBOT" content="NOSNIPPET">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bibliothèque de styles - Dotclear - 2.7</title>
  <link rel="icon" type="image/png" href="images/favicon96-login.png">
<?php
            echo
            Page::cssLoad('style/default.css') . // Set some JSON data
            Html::jsJson('dotclear_init', self::getJS());
        ?>
  <script src="js/jquery/jquery.js"></script>
  <script src="js/jquery/jquery-ui.custom.js"></script>
  <script src="js/jquery/jquery.ui.touch-punch.js"></script>
  <script src="js/prepend.js"></script>
  <script src="js/common.js"></script>
  <script src="js/prelude.js"></script>
  <script src="js/page-tabs-helper.js"></script>
  <script src="js/_charte.js"></script>
</head>

<body id="dotclear-admin" class="no-js guideline">
  <ul id="prelude">
    <li><a href="#content">Aller au contenu</a></li>
    <li><a href="#main-menu">Aller au menu</a></li>
    <li><a href="#qx">Aller à la recherche</a></li>
  </ul>
  <div id="header">
    <h1><a href="./index.php"><span class="hidden">Dotclear</span></a></h1>
    <div id="top-info-blog">
      <p>Bibliothèque de styles - Dotclear - 2.6+</p>
    </div>
    <ul id="top-info-user">
      <li>Octobre 2013</li>
    </ul>
  </div>
  <div id="wrapper" class="clearfix">
    <div class="hidden-if-no-js collapser-box"><button type="button" id="collapser" class="void-btn">
        <img class="collapse-mm visually-hidden" src="images/hide.svg" alt="Cacher le menu">
        <img class="expand-mm visually-hidden" src="images/expand.svg" alt="Montrer le menu">
        </button></div>
    <div id="main">
      <div id="content" class="clearfix">
        <h2>Typographie</h2>
        <h3 id="texte">Textes</h3>
        <p>La font-size de base est à 1.2rem (la valeur <code>1rem</code> correspond à 10px). Si vous utilisez l'unité <code>rem</code> pensez à faire précéder la déclaration par son équivalent
          en pixels pour rester compatible avec Internet Explorer. L'interlignage courant est à 1.5.</p>
        <p>La liste suivante est de class <code>"nice"</code>. Elle est semblable aux listes ordinaires mais avec des puces carrées.</p>
        <ul class="nice">
          <li>Les textes courants sont en Arial, Helvetica ou sans-serif. </li>
          <li>Le code adopte la fonte Andale Mono, Courier New ou monospace.</li>
          <li>Les liens ont des aspects différents au focus et au survol. Il faut conserver cette distinction, nécessaire à l'accessibilité et l'ergonomie.</li>
        </ul>
        <h3 id="titres">Titre h3</h3>
        <p>Le titre de niveau h1 est réservé au titre du site-admin. Le titre de niveau h2 est réservé au breadcrumb/titre de la page courante. On utilise les titres de niveau h3 en premier niveau de titre à l'intérieur des pages, comme sur la page Import/Export.</p>
        <p>Il ne faut pas choisir un niveau de titre en fonction de son aspect mais respecter une hiérarchie cohérente. On peut obtenir visuellement l'aspect d'un titre h3 en donnant à l'élément la class <code>"as_h3"</code>.</p>
        <h4>Titre de niveau h4</h4>
        <p>On peut obtenir visuellement l'aspect d'un titre h4 en donnant à l'élément la class <code>"as_h4"</code>.</p>
        <h5>Titre de niveau h5</h5>
        <p>Le titre de niveau h5 est assez peu employé mais son style est prévu. Dans une admin de base, on utilise les niveaux
          h5 pour certains éléments du sidebar du billet, mais un style particulier leur est alors appliqué pour ressembler aux autres
          items de ce sidebar.</p>
        <div class="fieldset">
          <h4>Titres des encadrés</h4>
          <p>Les titres de boîte encadrées (div de class <code>"fieldset"</code>, comme ici) se présentent comme ci-dessus.</p>
          <p>On peut utiliser, quel que soit le niveau hx de cet intertitre la class <code>"pretty-title"</code> pour obtenir l'effet ci-dessus.</p>
        </div>
        <h4 class="smart-title">Autre variante</h4>
        <p>On dispose également d'une class <code>"smart-title"</code> pour obtenir une présentation comme celle du titre de ce paragraphe.</p>
        <h2>Layouts</h2>
        <h3 id="onglets">Onglets</h3>
        <p>Les descriptions des constructions en multi-colonnes ci-dessous présentent un exemple de répartition en onglets.</p>
        <p>Chacun de ces onglets doit être défini à l'aide d'une <code>&lt;div class="multi-part"&gt;</code>. Ils seront alors automatiquement présentés sous forme d'onglets.</p>
        <h3 id="multi-colonnage">Multi-colonnage</h3>
        <div id="one-box" class="multi-part" title="One-box">
          <h4>Boîtes distribuées horizontalement</h4>
          <div class="one-box">
            <div class="box">
              <p><span class="step">1</span> Toutes les boîtes de class <code>"box"</code> placées à l'intérieur d'une boîte de class <code>"one-box"</code> se distribuent horizontalement (imaginez que chaque boîte est un mot dans un paragraphe). Si les largeurs de ces boîtes ne sont pas spécifiquement définies dans la CSS, elle s'ajustent à leur contenu.</p>
            </div>
            <div class="box">
              <p><span class="step">2</span> Voici une petite boîte.</p>
            </div>
            <div class="box">
              <p><span class="step">3</span> Une autre petite boîte.</p>
            </div>
            <div class="box">
              <p><span class="step">4</span> Par défaut les « lignes » de boîtes <code>"box"</code> sont justifiées au sein de la boîte <code>"one-box"</code> et l'espacement se répartit entre elles.</p>
            </div>
            <div class="box">
              <p><span class="step">5</span> Si vous souhaitez un autre alignement des boîtes entre elles vous pouvez ajouter les class :</p>
              <ul class="nice clear">
                <li><code>"txt-left"</code>,</li>
                <li><code>"txt-right"</code></li>
                <li>ou <code>"txt-center"</code></li>
              </ul>
              <p>à la class <code>"one-box"</code>.</p>
            </div>
            <div class="box">
              <p><span class="step">6</span> Le cadre placé ici autour de chaque boîte ne fait pas partie des styles par défaut.</p>
            </div>
          </div>
        </div>
        <div id="two-boxes" class="multi-part" title="Two-boxes">
          <h4>Boîtes distribuées deux par deux</h4>
          <div>
            <div class="two-boxes odd">
              <p><span class="step">1</span> Les boîtes de class <code>"two-boxes"</code> ont une règle CSS <code>display:inline-block;</code>. Elles se rangent alternativement à gauche et à droite. Pour plus de clarté, les blocs sont ici numérotés avec leur ordre dans le flux.</p>
            </div>
            <!--
                        -->
            <div class="two-boxes even">
              <p><span class="step">2</span> On peut assortir une boîte des class <code>"odd"</code> (nothing left) et <code>"even"</code> pour que les marges se placent correctement.</p>
            </div>
            <!--
                        -->
            <div class="two-boxes odd">
              <p><span class="step">3</span> Attention, il faut soit ne pas retourner à la ligne entre la fermeture d'une boîte <code>"two-boxes"</code> et l'ouverture de la suivante soit adopter la méthode de commentaire vide mise en place ici et expliquée chez <a href="https://www.alsacreations.com/astuce/lire/1432-display-inline-block-espaces-indesirables.html">Alsacréations</a> («&nbsp;Méthode 2&nbsp;»).</p>
            </div>
            <!--
                        -->
            <div class="two-boxes even">
              <div class="two-boxes odd">
                <p><span class="step">4</span> On peut bien sûr imbriquer des boîtes de class <code>"two-boxes"</code>
                  au sein d'une boîte <code>"two-boxes" afin qu'elles…</code>…</p>
              </div>
              <div class="two-boxes even">
                <p><span class="step">4 bis</span>… se distribuent horizontalement comme dans une boîte <code>"one-box"</code>.</p>
              </div>
            </div>
          </div>
        </div>
        <div id="three-boxes" class="multi-part" title="Three-boxes">
          <h4>Boîtes distribuées trois par trois</h4>
          <div>
            <div class="three-boxes">
              <div class="box">
                <p>Sur le même principe que les « two-boxes » on peut utiliser des boîtes de class <code>"three-boxes"</code> pour répartir les contenus sur trois colonnes de 30% chacune (le reste est occupé par les marges).</p>
              </div>
            </div>
            <!--
                        -->
            <div class="three-boxes">
              <div class="box">
                <p>Comme pour les "two-boxes" il faut soit ne pas laisser d'espace ou de retour à la ligne entre les boîtes, soit adopter la méthode recommandée plus haut.</p>
              </div>
            </div>
            <!--
                        -->
            <div class="three-boxes">
              <div class="box">
                <p>Dans les « two-boxes » comme dans les « three-boxes », on peut placer à l'intérieur plusieurs autres div de class="box" qui s'afficheront les unes à côté des autres ou l'une en dessous de l'autre selon la place dont elles disposent.</p>
              </div>
            </div>
          </div>
        </div>
        <div id="two-cols-50-50" class="multi-part" title="Two-cols (50/50)">
          <h4>Grille de deux colonnes de largeurs égales</h4>
          <div class="two-cols">
            <div class="col">
              <p>La div englobante porte la class <code>"two-cols"</code>, chacune de ses div porte la class <code>"col"</code>.
                Sans autre précision les deux colonnes sont d'égale largeur.</p>
            </div>
            <div class="col">
              <p>Voici une deuxième colonne.</p>
            </div>
            <div class="col100">
              <p>Voilà la troisième colonne avec une class <code>"col100"</code> qui permet de l'étendre sur toute la largeur.</p>
            </div>
            <div class="col">
              <p>Voici une quatrième colonne.</p>
            </div>
          </div>
        </div>
        <div id="two-cols-70-30" class="multi-part" title="Two-cols (70/30)">
          <h4>Grille de deux colonnes de largeurs inégales</h4>
          <div class="two-cols">
            <div class="col70">
              <p><span class="step">col70</span> La div englobante porte la class <code>"two-cols"</code>.
                Pour obtenir des colonnes inégales, on dispose des classes <code>"col70"</code> et <code>col30</code> à attribuer à l'une ou à l'autre de ses colonnes.</p>
            </div>
            <div class="col30">
              <p><span class="step">col30</span>Voici une deuxième petite colonne.</p>
            </div>
            <div class="col100">
              <p>Voilà une troisième colonne avec une class <code>"col100"</code> qui permet de l'étendre sur toute la largeur.</p>
            </div>
            <div class="col30">
              <p><span class="step">col30</span>Voici une quatrième petite colonne.</p>
            </div>
            <div class="col70">
              <p><span class="step">col70</span>Voici une cinquième grande colonne.</p>
            </div>
          </div>
        </div>
        <div id="three-cols" class="multi-part" title="Three-cols">
          <h4>Grille de trois colonnes de largeurs égales</h4>
          <div class="three-cols">
            <div class="col">
              <h5>Colonne 1</h5>
              <p>La div englobante porte la class <code>"three-cols"</code>, chacune de ses div porte la class <code>"col"</code>. Les trois colonnes sont d'égale largeur.</p>
            </div>
            <div class="col">
              <h5>Colonne 2</h5>
              <p>Voici une deuxième colonne.</p>
            </div>
            <div class="col">
              <h5>Colonne 3</h5>
              <p>Voilà la troisième colonne.</p>
            </div>
            <div class="col100">
              <h5>Colonne 4</h5>
              <p>Voilà la quatrième colonne avec une class <code>"col100"</code> qui permet de l'étendre sur toute la largeur.</p>
            </div>
            <div class="col">
              <h5>Colonne 5</h5>
              <p>Voici une cinquième petite colonne.</p>
            </div>
          </div>
        </div>
        <hr class="clear">
        <p><strong>Note :</strong> dans les exemples les valeurs et les numérotations sont placées dans un <code>span class="step"</code> (et ressortent donc dans un petit bloc à fond gris).</p>
        <h2>Interactions</h2>
        <h3 id="elements">Éléments de formulaire</h3>
        <p class="form-note">Les champs précédés par <span class="required">*</span> sont obligatoires.</p>
        <form class="two-cols clearfix" action="#">
          <div class="col">
            <p><label for="ex1">Label simple + input text :</label><input id="ex1" type="text"></p>
            <p class="form-note">p class="form-note".</p>
            <p><label for="ex4" class="classic">Label class="classic" + input text :</label> <input id="ex4" type="text"></p>
            <p><label for="ex2" class="required"><span title="Champ obligatoire">*</span> Label class="required" :</label> <input id="ex2" type="text" required placeholder="exemple"><span class="form-note">span class="form-note"</span></p>
            <p><label for="ex11" class="bold">Label class="bold" :</label> <input id="ex11" type="text"></p>
            <p class="form-note">La class="bold" est bien sûr à écrire en minuscules.</p>
            <p><label for="ex3">Input class="maximal" :</label> <input id="ex3" type="text" class="maximal"></p>
          </div>
          <div class="col">
            <p class="field"><label for="ex5">p.field label + input :</label><input id="ex5" type="text"></p>
            <p class="field"><label for="ex6">p.field label + select :</label>
              <select id="ex6">
                <option value="opt2">Option 2</option>
                <option selected="selected" value="opt2">Option 2</option>
              </select>
            </p>
            <p><label class="classic" for="ex7"><input type="checkbox" checked="checked" id="ex7" value="1" name="ex7">
                Checkbox (label.classic)</label></p>
            <p><label class="classic" for="ex8-1"><input type="radio" checked="checked" id="ex8-1" value="ex8-1" name="ex8-1">
                Bouton radio 1 (label.classic)</label></p>
            <p><label class="classic" for="ex8-2"><input type="radio" id="ex8-2" value="ex8-2" name="ex8-2">
                Bouton radio 2 (label.classic)</label></p>
            <p class="form-note">Les checkboxes et les boutons radio sont dans la balise &lt;label&gt;.</p>
            <p><label class="classic" for="ex9"><input type="checkbox" checked="checked" id="ex9" value="1" title="intitulé du champ"></label> <label for="ex10" class="classic">checkbox.classic + label class="classic" :</label> <input id="ex10" type="text"></p>
          </div>
        </form>
        <form action="#" class="clear">
          <fieldset>
            <legend>Légende de fieldset</legend>
            <p>Attention: Les fieldsets ne doivent être utilisés que pour isoler un groupe de champs au sein d'un formulaire.</p>
          </fieldset>
        </form>
        <h3 id="boutons">Boutons</h3>
        <div class="clearfix">
          <p class="form-buttons"><a class="button add">a.button.add</a> Se place en haut à droite (dans un p.top-add)</p>
          <p class="form-buttons"><input type="button" value="Type button"> <a href="#" class="button">a.button</a> <input type="reset" value="Type reset"> <a href="#" class="button reset">a.reset</a></p>
          <p class="form-buttons"><input type="submit" value="Type submit"> <input type="submit" class="delete" value="Type submit class delete"> <a href="#" class="button delete">a.button delete</a> <a href="#" class="button clone">a.button clone</a></p><p><a href="#" class="button checkbox-helper">a.button checkbox-helper</a></p>
          <p class="form-buttons"><input type="submit" value="Type submit class disabled" class="disabled"></p>
        </div>
        <h3 id="messages">Messages</h3>
        <h4 class="smart-title">Messages système</h4>
        <p>Il existe quatre types de messages système auxquels correspondent des classes CSS : .error, .message, .success, .warning-msg. Ils s'affichent en haut de page, sous le titre/breadcrumb.</p>
        <div class="message">
          <p>Message simple. Le plus souvent horodaté Notices::message</p>
        </div>
        <div class="success">
          <p>Message de succès. Le plus souvent horodaté Notices::success</p>
        </div>
        <div class="warning-msg">
          <p>Message warning. Non horodaté Notices::warning</p>
        </div>
        <div class="error">
          <p>Message d'erreur. Non horodaté Notices::error</p>
        </div>
        <p>La classe .static-msg peut être utilisée directement pour affichage en haut de page :</p>
        <div class="static-msg">
          <p>Comme le message simple mais sans effets de transition.</p>
        </div>
        <p>Un type de message réservé à Dotclear peut s'afficher en haut de la page :</p>
        <div class="dc-update">
          <h3>Dotclear 42 est disponible</h3>
          <p><a class="button submit" href="#">Mettre à jour maintenant</a>
            <a class="button" href="u#">Me le rappeler plus tard</a></p>
          <p class="updt-info"><a href="#">Informations sur cette version</a></p>
        </div>
        <h4 class="smart-title">Messages contextuels</h4>
        <p class="warn">Paragraphe de message d'alerte class warn ou warning.</p>
        <p class="info">Paragraphe de message de class info.</p>
        <p>Ces messages sont en display:inline-block. Le fond s'adapte à la longueur du message.</p>
        <h2>Navigation</h2>
        <h3 id="direct">Selecteur d'accès direct</h3>
        <p>Sur des pages longues et denses comme les pages about:config ou about:preferences, on peut utiliser un sélecteur pour faciliter l'accès direct aux sections.</p>
        <p class="anchor-nav">
          <label class="classic" for="lp_nav">Aller à : </label>
          <select id="lp_nav" name="lp_nav">
            <option value="#l_accessibility">accessibility</option>
            <option value="#l_dashboard">dashboard</option>
            <option value="#l_dmhostingmonitor">dmhostingmonitor</option>
            <option value="#l_dmpending">dmpending</option>
            <option value="#l_favorites">favorites</option>
            <option value="#l_filters">filters</option>
            <option value="#l_interface">interface</option>
            <option value="#l_lists">lists</option>
            <option value="#l_toggles">toggles</option>
          </select>
          <input type="submit" id="lp_submit" value="Ok" style="display: none;">
          <input type="hidden" value="aboutConfig" name="p">
        </p>
        <h3 id="prevnext">Navigation contextuelle</h3>
        <p><a title="Titre du lien" href="https://fr.dotclear.org/blog" class="onblog_link outgoing">Lien vers le blog <img alt="" src="images/outgoing-link.svg"></a></p>
        <p class="nav_prevnext"><a title="Titre de l'élément précédente" href="post.php?id=4145">«&nbsp;Élément précédent</a> | <a title="Titre de l'élément suivant" href="#">Élément suivant&nbsp;»</a></p>
        <h3 id="pseudo-tabs">Pseudo-onglets </h3>
        <p>Les pseudo-onglets permettent d'ajouter des sous-pages qui sont des liens vers d'autres pages, par opposition aux onglets qui sont des sections internes à la page.</p>
        <p>Les pseudo-onglets sont à positionner immédiatement après le breadcrumb (ici un hr simule le trait sous le breadcrumb).</p>
        <p>Ces pseudo-onglets doivent être définis avec un <code>&lt;ul class="pseudo-tabs"&gt;</code> et des <code>&lt;li&gt;</code>.</p>
        <hr style="margin-bottom: .75em;">
        <ul class="pseudo-tabs">
          <li><a href="#">Page 1</a></li>
          <li><a href="#">Autre faux onglet</a></li>
          <li><a href="#" class="active">Onglet actif</a></li>
          <li><a href="#">Liste 4</a></li>
        </ul>
        <h2 id="common">Tableaux</h2>
        <p>Il existe deux mises en forme type de tableaux selon que l'on cherche à faire un tableau ordinaire
          ou un tableau dont on peut déplacer les lignes par glisser déposer (voir plus bas). Cependant certaines règles
          sont communes à tout les tableaux.</p>
        <h3>Règles communes</h3>
        <h4>Largeur du tableau</h4>
        <p>Sauf pour des tableaux particuliers (absents dans l'admin mais qui pourraient être nécessaires
          à un plugin,les tableaux occupent toute la largeur de la page. Afin que les tableaux soient consultables
          sur un mobile en navigant horizontalement, on englobe le tableau dans une <code>div class="table-outer"</code>,
          qui servira de «&nbsp;conteneur&nbsp;».</p>
        <h4>Accessibilité</h4>
        <p>Les éléments caption, th, scope sont nécessaires à l'accessibilité. Ne les oubliez pas&nbsp;!&nbsp;».
          On peut utiliser la <code>class="hidden"</code> sur l'élément <code>caption</code> (qui accueille
          le titre du tableau) si vous ne souhaitez pas qu'il soit affiché sur la page.</p>
        <h4>Les classes</h4>
        <p>Des classes particulières peuvent être attribuées aux lignes :</p>
        <ul>
          <li><code>line</code> (systématique) : pour les traits horizontaux et le fond gris léger
            au survol&nbsp;;</li>
          <li><code>offline</code> : pour un noir estompé (gris quoi).</li>
        </ul>
        <p>Des classes particulières peuvent être appliquées aux cellules :</p>
        <ul>
          <li><code>nowrap</code> : pas de retour à la ligne dans la cellule, quelle que soit la
            largeur de la page&nbsp;;</li>
          <li><code>maximal</code> : la cellule prendra toute la largeur restante disponible&nbsp;;</li>
          <li><code>count</code> : le contenu de la cellule sera aligné à droite avec un petit retrait.</li>
        </ul>
        <h3 id="courants">Tableau classique</h3>
        <div class="table-outer">
          <table>
            <caption class="hidden">Liste des publications</caption>
            <tr>
              <th colspan="2" class="first">Titre</th>
              <th scope="col">Date</th>
              <th scope="col">Catégorie</th>
              <th scope="col">Auteur</th>
              <th scope="col">Commentaires</th>
              <th scope="col">Rétroliens</th>
              <th scope="col">État</th>
            </tr>
            <tr class="line">
              <td class="nowrap"><input type="checkbox" name="name1" value="value1"></td>
              <td class="maximal"><a href="#">Mon cher Franck</a></td>
              <td class="nowrap count">06/08/2013 19:16</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">4</td>
              <td class="nowrap count">0</td>
              <td class="nowrap status"><img alt="Publié" class="mark mark-published" src="images/published.svg"> <img alt="Sélectionné" title="Sélectionné" class="mark mark-selected" src="images/selected.svg">  </td>
            </tr>
            <tr class="line offline">
              <td class="nowrap"><input type="checkbox" name="name2" value="value2"></td>
              <td class="maximal"><a href="#">Dotclear 2.3.0</a></td>
              <td class="nowrap count">16/05/2011 22:29</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">5</td>
              <td class="nowrap count">0</td>
              <td class="nowrap status"><img alt="Non publié" class="mark mark-unpublished" src="images/unpublished.svg"> <img alt="Sélectionné" title="Sélectionné" class="mark mark-selected" src="images/selected.svg">  </td>
            </tr>
            <tr class="line">
              <td class="nowrap"><input type="checkbox" name="entries[]" value="2148"></td>
              <td class="maximal"><a href="#">Causons opéra au Tamm Bara</a></td>
              <td class="nowrap count">24/11/2009 23:10</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">4</td>
              <td class="nowrap count">1</td>
              <td class="nowrap status"><img alt="Publié" class="mark mark-published" src="images/published.svg">   </td>
            </tr>
            <tr class="line">
              <td class="nowrap"><input type="checkbox" name="entries[]" value="2136"></td>
              <td class="maximal"><a href="#">Souffler six bougies</a></td>
              <td class="nowrap count">14/08/2009 00:00</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">4</td>
              <td class="nowrap count">2</td>
              <td class="nowrap status"><img alt="Publié" class="mark mark-published" src="images/published.svg">   </td>
            </tr>
            <tr class="line">
              <td class="nowrap"><input type="checkbox" name="entries[]" value="2129"></td>
              <td class="maximal"><a href="#">Dotclear et grenadine, troisième édition</a></td>
              <td class="nowrap count">15/06/2009 07:39</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">9</td>
              <td class="nowrap count">1</td>
              <td class="nowrap status"><img alt="Publié" class="mark mark-published" src="images/published.svg">   </td>
            </tr>
            <tr class="line">
              <td class="nowrap"><input type="checkbox" name="entries[]" value="2111"></td>
              <td class="maximal"><a href="#">L'abc dotclear est né</a></td>
              <td class="nowrap count">19/03/2009 10:31</td>
              <td class="nowrap"><a href="#">Les aventures du clafoutis</a></td>
              <td class="nowrap">kozlika</td>
              <td class="nowrap count">1</td>
              <td class="nowrap count">0</td>
              <td class="nowrap status"><img alt="Publié" class="mark mark-published" src="images/published.svg">   </td>
            </tr>
          </table>
        </div>
        <h3 id="dragable">Tableau avec ordonnancement</h3>
        <p>Les tableaux permettant l'ordonnancement doivent offrir la possibilité d'effectuer le classement grâce à
          des inputs placés en début de ligne pour que le classement soit possible même lorsque cette fonctionnalité est
          désactivée (via les préférences utilisateurs, voire une désactivation complète du javascript dans le navigateur).</p>
        <div class="table-outer">
          <table class="maximal dragable">
              <caption class="hidden">Liste des publications</caption>
            <thead>
              <tr>
                <th colspan="3">Titre</th>
                <th>Date</th>
                <th>Auteur</th>
                <th>Commentaires</th>
                <th>Rétroliens</th>
                <th>État</th>
              </tr>
            </thead>
            <tbody id="pageslist">
              <tr class="line" id="p10899">
                <td class="nowrap handle minimal">
                  <input type="text" size="2" name="order[10899]" maxlength="3" value="1" class="position" title="position de Mentions légales">
                </td>
                <td class="nowrap">
                  <input type="checkbox" name="entries[]" value="10899" title="Sélectionner cette page">
                </td>
                <td class="maximal"><a href="#">Mentions légales</a>
                </td>
                <td class="nowrap">17/12/2008 07:35</td>
                <td class="nowrap">franck</td>
                <td class="nowrap">0</td>
                <td class="nowrap">0</td>
                <td class="nowrap status">
                  <img alt="Publié" class="mark mark-published" src="images/published.svg">
                                    </td>
              </tr>
              <tr class="line" id="p10937">
                <td class="nowrap handle minimal">
                  <input type="text" size="2" name="order[10937]" maxlength="3" value="2" class="position" title="position de Page active et cachée">
                </td>
                <td class="nowrap">
                  <input type="checkbox" name="entries[]" value="10937" title="Sélectionner cette page">
                </td>
                <td class="maximal"><a href="#">Page active et cachée</a>
                </td>
                <td class="nowrap">26/10/2012 11:08</td>
                <td class="nowrap">admin</td>
                <td class="nowrap">0</td>
                <td class="nowrap">0</td>
                <td class="nowrap status">
                  <img alt="Publié" class="mark mark-published" src="images/published.svg">
                  <img alt="Masqué" class="mark mark-hidden" src="images/hidden.svg">
                                    </td>
              </tr>
              <tr class="line offline" id="p11047">
                <td class="nowrap handle minimal">
                  <input type="text" size="2" name="order[11047]" maxlength="3" value="3" class="position" title="position de Page révisionnable">
                </td>
                <td class="nowrap">
                  <input type="checkbox" name="entries[]" value="11047" title="Sélectionner cette page">
                </td>
                <td class="maximal"><a href="#">Page révisionnable</a>
                </td>
                <td class="nowrap">14/12/2012 13:26</td>
                <td class="nowrap">admin</td>
                <td class="nowrap">0</td>
                <td class="nowrap">0</td>
                <td class="nowrap status">
                  <img alt="En attente" class="mark mark-pending" src="images/pending.svg">
                                    </td>
              </tr>
              <tr class="line offline" id="p10939">
                <td class="nowrap handle minimal">
                  <input type="text" size="2" name="order[10939]" maxlength="3" value="4" class="position" title="position de Programme">
                </td>
                <td class="nowrap">
                  <input type="checkbox" name="entries[]" value="10939" title="Sélectionner cette page">
                </td>
                <td class="maximal"><a href="#">Programme</a>
                </td>
                <td class="nowrap">26/10/2020 11:23</td>
                <td class="nowrap">admin</td>
                <td class="nowrap">0</td>
                <td class="nowrap">0</td>
                <td class="nowrap status">
                  <img alt="Programmé" class="mark mark-scheduled" src="images/scheduled.svg">
                                    </td>
              </tr>
              <tr class="line offline" id="p10940">
                <td class="nowrap handle minimal">
                  <input type="text" size="2" name="order[10940]" maxlength="3" value="5" class="position" title="position de Protégée">
                </td>
                <td class="nowrap">
                  <input type="checkbox" name="entries[]" value="10940" title="Sélectionner cette page">
                </td>
                <td class="maximal"><a href="#">Protégée</a>
                </td>
                <td class="nowrap">26/10/2012 11:23</td>
                <td class="nowrap">admin</td>
                <td class="nowrap">0</td>
                <td class="nowrap">0</td>
                <td class="nowrap status">
                  <img alt="En attente" src="images/pending.svg" class="mark mark-pending"><img alt="Protégé" src="images/locker.svg" class="mark mark-locked">
                                    </td>
              </tr>
            </tbody>
          </table>
        </div>
        <h2 id="iconset">Icônes</h2>
        <p>Les icônes utilisées dans l'administration sont présentes en deux formats&nbsp; 64*64px pour les grandes
          (qui sont affichées sur le tableau de bord si la page correspondante est choisie en favori par l'utilisateur) et
          16*16px pour les petits formats.</p>
        <p>La plupart sont dérivées de la fonte d'icônes <a href="https://www.elegantthemes.com/blog/resources/elegant-icon-font">Elegant Font</a>. Les autres sont des images vectorielles réalisées
          par la DC Team. Nous les avons nommées <em>Traviata</em>. La palette de couleurs utilisée est la suivante&nbsp;:</p>
        <p class="txt-center"><img class="palette" src="images/palette-traviata.svg" alt="palette des couleurs utilisées pour les icônes">Bleu&nbsp;: #137bbb - Vert&nbsp;: #9ac123 - Rouge&nbsp;: #c44d58 - Bleu ciel&nbsp;: #a2cbe9 - Gris clair&nbsp;: #ececec -
            Gris moyen&nbsp;: #b2b2b2 - Gris foncé&nbsp;: #676e78.</p>
      <div class="info vertical-separator">
        <p>Cette page vise à présenter les règles graphiques et conventions utilisées dans les pages de l'administration
          d'une installation Dotclear, à l'usage des contributeurs et développeurs d'extensions. Elle en est elle-même
          une illustration. L'observation de son code source peut donc servir de complément aux descriptions.</p>
      </div>
      </div><!-- /content -->
    </div><!-- /main -->
    <div id="main-menu">
      <ul>
        <li class="pretty-title">Typographie
          <ul>
            <li><a href="#texte">Texte</a></li>
            <li><a href="#titres">Titres hx</a></li>
          </ul>
        </li>
        <li class="pretty-title">Layouts
          <ul>
            <li><a href="#onglets">Onglets</a></li>
            <li><a href="#multi-colonnage">Multi-colonnage</a></li>
          </ul>
        </li>
        <li class="pretty-title">Interactions
          <ul>
            <li><a href="#elements">Éléments de formulaire</a></li>
            <li><a href="#boutons">Boutons</a></li>
            <li><a href="#messages">Messages</a></li>
          </ul>
        </li>
        <li class="pretty-title">Navigation
          <ul>
            <li><a href="#direct">Accès direct</a></li>
            <li><a href="#prevnext">Précédent, suivant</a></li>
            <li><a href="#pseudo-tabs">Pseudo-onglets</a></li>
          </ul>
        </li>
        <li class="pretty-title">Tableaux
          <ul>
            <li><a href="#commons">Règles communes</a></li>
            <li><a href="#courants">Tableaux courants</a></li>
            <li><a href="#dragables">Tableaux ordonnancés</a></li>
          </ul>
        </li>
      </ul>
    </div>
    <div id="footer">
      <a href="https://dotclear.org/" title="Merci de manger des clafoutis."><img src="style/dc_logos/dotclear-light.svg" class="light-only" alt=""><img src="style/dc_logos/dotclear-dark.svg" class="dark-only" alt=""></a>
    </div>
  </div>
</body>

</html>
<?php
    }
}
