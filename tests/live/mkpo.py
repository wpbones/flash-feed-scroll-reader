# Builds the five .po/.mo catalogues in languages/ from the .pot (needs msgfmt); run by hand when the strings change.
import re, subprocess, sys, os
ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', '..', 'languages')  # the plugin's languages/ folder
POT = ROOT + '/flash-feed-scroll-reader.pot'
LOCALES = ['it_IT', 'es_ES', 'fr_FR', 'de_DE', 'pt_BR']
LANGNAME = {'it_IT': 'Italian', 'es_ES': 'Spanish', 'fr_FR': 'French', 'de_DE': 'German', 'pt_BR': 'Portuguese (Brazil)'}
PLURAL = {'it_IT': 'nplurals=2; plural=n != 1;', 'es_ES': 'nplurals=2; plural=n != 1;', 'fr_FR': 'nplurals=2; plural=n > 1;', 'de_DE': 'nplurals=2; plural=n != 1;', 'pt_BR': 'nplurals=2; plural=n > 1;'}

# key (start of the English msgid) -> (it, es, fr, de, pt)
T = {
 'Feed Scroll Reader': ('Feed Scroll Reader',) * 5,
 'A horizontal scrolling ticker of items from one or more RSS/Atom feeds. Plain HTML': (
   'Un ticker a scorrimento orizzontale con gli elementi di uno o più feed RSS/Atom. Solo HTML e CSS, niente Flash. Già Flash Feed Scroll Reader.',
   'Un ticker de desplazamiento horizontal con los elementos de uno o más feeds RSS/Atom. Solo HTML y CSS, sin Flash. Antes Flash Feed Scroll Reader.',
   'Un bandeau défilant horizontal avec les éléments d’un ou plusieurs flux RSS/Atom. HTML et CSS uniquement, sans Flash. Anciennement Flash Feed Scroll Reader.',
   'Ein horizontal laufendes Ticker-Band mit den Einträgen eines oder mehrerer RSS-/Atom-Feeds. Nur HTML und CSS, kein Flash. Früher Flash Feed Scroll Reader.',
   'Um ticker de rolagem horizontal com os itens de um ou mais feeds RSS/Atom. Apenas HTML e CSS, sem Flash. Antes Flash Feed Scroll Reader.'),
 'Settings': ('Impostazioni', 'Ajustes', 'Réglages', 'Einstellungen', 'Configurações'),
 'Sorry, you are not allowed to access this page.': ('Non hai i permessi per accedere a questa pagina.', 'Lo siento, no tienes permiso para acceder a esta página.', 'Désolé, vous n’avez pas l’autorisation d’accéder à cette page.', 'Du hast keine Berechtigung, auf diese Seite zuzugreifen.', 'Desculpe, você não tem permissão para acessar esta página.'),
 'Sorry, you are not allowed to do that.': ('Non hai i permessi per farlo.', 'Lo siento, no tienes permiso para hacer eso.', 'Désolé, vous n’avez pas l’autorisation de faire cela.', 'Du hast keine Berechtigung dafür.', 'Desculpe, você não tem permissão para fazer isso.'),
 'No feed is set:': ('Nessun feed impostato: aggiungine uno in Impostazioni > Feed Scroll Reader.', 'No hay ningún feed: añade uno en Ajustes > Feed Scroll Reader.', 'Aucun flux n’est défini : ajoutez-en un dans Réglages > Feed Scroll Reader.', 'Es ist kein Feed eingetragen: Füge unter Einstellungen > Feed Scroll Reader einen hinzu.', 'Nenhum feed definido: adicione um em Configurações > Feed Scroll Reader.'),
 'The feeds returned no items.': ('I feed non hanno restituito elementi.', 'Los feeds no devolvieron elementos.', 'Les flux n’ont renvoyé aucun élément.', 'Die Feeds haben keine Einträge geliefert.', 'Os feeds não retornaram itens.'),
 'Latest items': ('Ultimi elementi', 'Últimos elementos', 'Derniers éléments', 'Neueste Einträge', 'Itens mais recentes'),
 'A horizontal scrolling ticker of items from one or more RSS/Atom feeds.': ('Un ticker a scorrimento orizzontale con gli elementi di uno o più feed RSS/Atom.', 'Un ticker de desplazamiento horizontal con los elementos de uno o más feeds RSS/Atom.', 'Un bandeau défilant horizontal avec les éléments d’un ou plusieurs flux RSS/Atom.', 'Ein horizontal laufendes Ticker-Band mit den Einträgen eines oder mehrerer RSS-/Atom-Feeds.', 'Um ticker de rolagem horizontal com os itens de um ou mais feeds RSS/Atom.'),
 'Settings saved.': ('Impostazioni salvate.', 'Ajustes guardados.', 'Réglages enregistrés.', 'Einstellungen gespeichert.', 'Configurações salvas.'),
 'These lines are not web addresses': ('Queste righe non sono indirizzi web e non sono state salvate:', 'Estas líneas no son direcciones web y no se guardaron:', 'Ces lignes ne sont pas des adresses web et n’ont pas été enregistrées :', 'Diese Zeilen sind keine Webadressen und wurden nicht gespeichert:', 'Estas linhas não são endereços web e não foram salvas:'),
 'A horizontal scrolling ticker of the latest items': ('Un ticker a scorrimento orizzontale con gli ultimi elementi di uno o più feed. Questi sono i valori predefiniti di ogni ticker; uno shortcode, un widget o un template tag può cambiarne qualcuno solo per sé.', 'Un ticker de desplazamiento horizontal con los últimos elementos de uno o más feeds. Estos son los valores predeterminados de cada ticker; un shortcode, un widget o una template tag puede cambiar cualquiera de ellos solo para sí.', 'Un bandeau défilant horizontal avec les derniers éléments d’un ou plusieurs flux. Ce sont les valeurs par défaut de chaque bandeau ; un shortcode, un widget ou une template tag peut en changer pour lui seul.', 'Ein horizontal laufendes Ticker-Band mit den neuesten Einträgen eines oder mehrerer Feeds. Das sind die Standardwerte jedes Tickers; ein Shortcode, ein Widget oder ein Template-Tag kann jeden davon nur für sich ändern.', 'Um ticker de rolagem horizontal com os itens mais recentes de um ou mais feeds. Estes são os valores padrão de cada ticker; um shortcode, um widget ou uma template tag pode alterar qualquer um deles só para si.'),
 'Feeds': ('Feed', 'Feeds', 'Flux', 'Feeds', 'Feeds'),
 'Feed addresses': ('Indirizzi dei feed', 'Direcciones de los feeds', 'Adresses des flux', 'Feed-Adressen', 'Endereços dos feeds'),
 'One RSS or Atom address per line, up to 5.': ('Un indirizzo RSS o Atom per riga, fino a 5. Più feed vengono uniti, dal più recente.', 'Una dirección RSS o Atom por línea, hasta 5. Varios feeds se combinan, del más reciente al más antiguo.', 'Une adresse RSS ou Atom par ligne, 5 au maximum. Plusieurs flux sont fusionnés, du plus récent au plus ancien.', 'Eine RSS- oder Atom-Adresse pro Zeile, bis zu 5. Mehrere Feeds werden zusammengeführt, die neuesten zuerst.', 'Um endereço RSS ou Atom por linha, até 5. Vários feeds são combinados, do mais recente ao mais antigo.'),
 'Items': ('Elementi', 'Elementos', 'Éléments', 'Einträge', 'Itens'),
 'How many items the ticker shows in all': ('Quanti elementi mostra in tutto il ticker, da 1 a 100.', 'Cuántos elementos muestra el ticker en total, de 1 a 100.', 'Combien d’éléments le bandeau affiche en tout, de 1 à 100.', 'Wie viele Einträge das Ticker-Band insgesamt zeigt, von 1 bis 100.', 'Quantos itens o ticker mostra no total, de 1 a 100.'),
 'Refresh every': ('Aggiorna ogni', 'Actualizar cada', 'Actualiser toutes les', 'Aktualisieren alle', 'Atualizar a cada'),
 'minutes': ('minuti', 'minutos', 'minutes', 'Minuten', 'minutos'),
 'A feed is downloaded again': ('Un feed viene scaricato di nuovo dopo questo tempo; nel frattempo si usa la copia salvata.', 'Un feed se descarga de nuevo pasado este tiempo; entre dos descargas se usa la copia guardada.', 'Un flux est téléchargé de nouveau après ce délai ; entre deux téléchargements, la copie enregistrée est utilisée.', 'Ein Feed wird nach dieser Zeit erneut geladen; dazwischen wird die gespeicherte Kopie verwendet.', 'Um feed é baixado novamente após esse tempo; entre dois downloads a cópia salva é usada.'),
 'Look': ('Aspetto', 'Aspecto', 'Apparence', 'Aussehen', 'Aparência'),
 'Width': ('Larghezza', 'Ancho', 'Largeur', 'Breite', 'Largura'),
 'A number with a unit:': ('Un numero con unità: 100%, 480px, 30em. Un numero da solo è in pixel.', 'Un número con unidad: 100%, 480px, 30em. Un número solo son píxeles.', 'Un nombre avec une unité : 100%, 480px, 30em. Un nombre seul est en pixels.', 'Eine Zahl mit Einheit: 100%, 480px, 30em. Eine Zahl allein bedeutet Pixel.', 'Um número com unidade: 100%, 480px, 30em. Um número sozinho é em pixels.'),
 'Height': ('Altezza', 'Alto', 'Hauteur', 'Höhe', 'Altura'),
 'Like the width, or auto.': ('Come la larghezza, oppure auto. Il valore predefinito segue la dimensione del testo.', 'Como el ancho, o auto. El valor predeterminado sigue el tamaño del texto.', 'Comme la largeur, ou auto. Par défaut, elle suit la taille du texte.', 'Wie die Breite, oder auto. Der Standard folgt der Textgröße.', 'Como a largura, ou auto. O padrão acompanha o tamanho do texto.'),
 'Background color': ('Colore di sfondo', 'Color de fondo', 'Couleur de fond', 'Hintergrundfarbe', 'Cor de fundo'),
 'Empty: transparent': ('Vuoto: trasparente, si vede la pagina sotto.', 'Vacío: transparente, se ve la página detrás.', 'Vide : transparent, la page se voit à travers.', 'Leer: transparent, die Seite scheint durch.', 'Vazio: transparente, a página aparece por trás.'),
 'Text color': ('Colore del testo', 'Color del texto', 'Couleur du texte', 'Textfarbe', 'Cor do texto'),
 'Empty: the color of the surrounding text.': ('Vuoto: il colore del testo circostante. Il carattere è sempre quello del tema.', 'Vacío: el color del texto circundante. La fuente es siempre la del tema.', 'Vide : la couleur du texte environnant. La police est toujours celle du thème.', 'Leer: die Farbe des umgebenden Textes. Die Schrift ist immer die des Themes.', 'Vazio: a cor do texto ao redor. A fonte é sempre a do tema.'),
 'Behavior': ('Comportamento', 'Comportamiento', 'Comportement', 'Verhalten', 'Comportamento'),
 'Speed': ('Velocità', 'Velocidad', 'Vitesse', 'Geschwindigkeit', 'Velocidade'),
 'pixels per second': ('pixel al secondo', 'píxeles por segundo', 'pixels par seconde', 'Pixel pro Sekunde', 'pixels por segundo'),
 'The ticker stops while the pointer': ('Il ticker si ferma mentre il puntatore è sopra o un link ha il focus da tastiera, e non si muove affatto per chi chiede al sistema di ridurre le animazioni.', 'El ticker se detiene mientras el puntero está encima o un enlace tiene el foco del teclado, y no se mueve en absoluto para quien pide al sistema reducir el movimiento.', 'Le bandeau s’arrête tant que le pointeur le survole ou qu’un lien a le focus clavier, et ne bouge pas du tout pour les visiteurs qui demandent à leur système de réduire les animations.', 'Das Ticker-Band hält an, solange der Mauszeiger darüber steht oder ein Link den Tastaturfokus hat, und bewegt sich gar nicht für Besucher, die ihr System um reduzierte Bewegung bitten.', 'O ticker para enquanto o ponteiro está sobre ele ou um link tem o foco do teclado, e não se move para quem pede ao sistema movimento reduzido.'),
 'Separator': ('Separatore', 'Separador', 'Séparateur', 'Trennzeichen', 'Separador'),
 'Plain text between two items.': ('Testo semplice tra due elementi. Vuoto: nessuno.', 'Texto sencillo entre dos elementos. Vacío: ninguno.', 'Texte simple entre deux éléments. Vide : aucun.', 'Einfacher Text zwischen zwei Einträgen. Leer: keines.', 'Texto simples entre dois itens. Vazio: nenhum.'),
 'Descriptions': ('Descrizioni', 'Descripciones', 'Descriptions', 'Beschreibungen', 'Descrições'),
 'Show the description after each title': ('Mostra la descrizione dopo ogni titolo', 'Mostrar la descripción después de cada título', 'Afficher la description après chaque titre', 'Beschreibung nach jedem Titel anzeigen', 'Mostrar a descrição após cada título'),
 'Cut it to': ('Taglia a', 'Cortar a', 'Couper à', 'Kürzen auf', 'Cortar em'),
 'characters, and end it with': ('caratteri, e termina con', 'caracteres, y terminar con', 'caractères, et terminer par', 'Zeichen und beenden mit', 'caracteres, e terminar com'),
 'Text at the end of a cut description': ('Testo alla fine di una descrizione tagliata', 'Texto al final de una descripción cortada', 'Texte à la fin d’une description coupée', 'Text am Ende einer gekürzten Beschreibung', 'Texto no fim de uma descrição cortada'),
 'Open links': ('Apri i link', 'Abrir los enlaces', 'Ouvrir les liens', 'Links öffnen', 'Abrir os links'),
 'In a new tab': ('In una nuova scheda', 'En una pestaña nueva', 'Dans un nouvel onglet', 'In einem neuen Tab', 'Em uma nova guia'),
 'In the same tab': ('Nella stessa scheda', 'En la misma pestaña', 'Dans le même onglet', 'Im selben Tab', 'Na mesma guia'),
 'Label for screen readers': ('Etichetta per gli screen reader', 'Etiqueta para lectores de pantalla', 'Libellé pour les lecteurs d’écran', 'Bezeichnung für Screenreader', 'Rótulo para leitores de tela'),
 'The name of the ticker for assistive': ('Il nome del ticker per le tecnologie assistive.', 'El nombre del ticker para las tecnologías de apoyo.', 'Le nom du bandeau pour les technologies d’assistance.', 'Der Name des Tickers für Hilfstechnologien.', 'O nome do ticker para tecnologias assistivas.'),
 'Preview': ('Anteprima', 'Vista previa', 'Aperçu', 'Vorschau', 'Pré-visualização'),
 'The saved defaults, as a visitor': ('I valori predefiniti salvati, come li vede un visitatore.', 'Los valores predeterminados guardados, como los ve un visitante.', 'Les valeurs par défaut enregistrées, telles qu’un visiteur les voit.', 'Die gespeicherten Standardwerte, so wie ein Besucher sie sieht.', 'Os padrões salvos, como um visitante os vê.'),
 'Use it': ('Come usarlo', 'Cómo usarlo', 'Utilisation', 'Verwendung', 'Como usar'),
 'Shortcode': ('Shortcode', 'Shortcode', 'Shortcode', 'Shortcode', 'Shortcode'),
 'Attributes: feed': ('Attributi: feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Ognuno è facoltativo e ripiega sui valori predefiniti qui sopra.', 'Atributos: feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Cada uno es opcional y usa los valores predeterminados de arriba.', 'Attributs : feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Chacun est facultatif et reprend les valeurs par défaut ci-dessus.', 'Attribute: feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Jedes ist optional und fällt auf die obigen Standardwerte zurück.', 'Atributos: feed, max_items, speed, width, height, bgcolor, color, description, description_length, suffix, separator, target, cache, label. Cada um é opcional e usa os padrões acima.'),
 'Widget': ('Widget', 'Widget', 'Widget', 'Widget', 'Widget'),
 'Appearance > Widgets': ('Aspetto > Widget > Feed Scroll Reader.', 'Apariencia > Widgets > Feed Scroll Reader.', 'Apparence > Widgets > Feed Scroll Reader.', 'Design > Widgets > Feed Scroll Reader.', 'Aparência > Widgets > Feed Scroll Reader.'),
 'Template tag': ('Template tag', 'Template tag', 'Template tag', 'Template-Tag', 'Template tag'),
 'The same two functions as before': ('Le stesse due funzioni di prima della versione 2. I vecchi argomenti funzionano ancora; stylesheet, wmode e gli altri che avevano senso solo per il filmato Flash sono ignorati.', 'Las mismas dos funciones de antes de la versión 2. Los argumentos antiguos siguen funcionando; stylesheet, wmode y los demás que solo tenían sentido para la película Flash se ignoran.', 'Les deux mêmes fonctions qu’avant la version 2. Les anciens arguments fonctionnent toujours ; stylesheet, wmode et les autres, qui n’avaient de sens que pour l’animation Flash, sont ignorés.', 'Dieselben zwei Funktionen wie vor Version 2. Die alten Argumente funktionieren weiter; stylesheet, wmode und die anderen, die nur für den Flash-Film Sinn hatten, werden ignoriert.', 'As mesmas duas funções de antes da versão 2. Os argumentos antigos continuam funcionando; stylesheet, wmode e os outros que só faziam sentido para o filme Flash são ignorados.'),
 'Title:': ('Titolo:', 'Título:', 'Titre :', 'Titel:', 'Título:'),
 'Feed URLs, one per line (up to 5):': ('Indirizzi dei feed, uno per riga (fino a 5):', 'Direcciones de los feeds, una por línea (hasta 5):', 'Adresses des flux, une par ligne (5 au maximum) :', 'Feed-Adressen, eine pro Zeile (bis zu 5):', 'Endereços dos feeds, um por linha (até 5):'),
 'Pause scrolling': ('Metti in pausa lo scorrimento', 'Pausar el desplazamiento', 'Mettre le défilement en pause', 'Scrollen anhalten', 'Pausar a rolagem'),
 '_DROP_': ('Indirizzi dei feed, uno per riga:', 'Direcciones de los feeds, una por línea:', 'Adresses des flux, une par ligne :', 'Feed-Adressen, eine pro Zeile:', 'Endereços dos feeds, um por linha:'),
 'Empty: the feeds of Settings': ('Vuoto: i feed di Impostazioni > Feed Scroll Reader.', 'Vacío: los feeds de Ajustes > Feed Scroll Reader.', 'Vide : les flux de Réglages > Feed Scroll Reader.', 'Leer: die Feeds aus Einstellungen > Feed Scroll Reader.', 'Vazio: os feeds de Configurações > Feed Scroll Reader.'),
 'Items:': ('Elementi:', 'Elementos:', 'Éléments :', 'Einträge:', 'Itens:'),
 'Speed (px/s):': ('Velocità (px/s):', 'Velocidad (px/s):', 'Vitesse (px/s) :', 'Geschwindigkeit (px/s):', 'Velocidade (px/s):'),
 'Height:': ('Altezza:', 'Alto:', 'Hauteur :', 'Höhe:', 'Altura:'),
 'Descriptions:': ('Descrizioni:', 'Descripciones:', 'Descriptions :', 'Beschreibungen:', 'Descrições:'),
 'Default': ('Predefinito', 'Predeterminado', 'Par défaut', 'Standard', 'Padrão'),
 'Show': ('Mostra', 'Mostrar', 'Afficher', 'Anzeigen', 'Mostrar'),
 'Hide': ('Nascondi', 'Ocultar', 'Masquer', 'Ausblenden', 'Ocultar'),
 'Empty fields use the defaults': ('I campi vuoti usano i valori predefiniti di Impostazioni > Feed Scroll Reader.', 'Los campos vacíos usan los valores predeterminados de Ajustes > Feed Scroll Reader.', 'Les champs vides utilisent les valeurs par défaut de Réglages > Feed Scroll Reader.', 'Leere Felder verwenden die Standardwerte aus Einstellungen > Feed Scroll Reader.', 'Campos vazios usam os padrões de Configurações > Feed Scroll Reader.'),
 'Ticker': ('Ticker', 'Ticker', 'Bandeau', 'Ticker', 'Ticker'),
 'A number with a unit (40px': ('Un numero con unità (40px, 3em) oppure auto. Un numero da solo è in pixel.', 'Un número con unidad (40px, 3em) o auto. Un número solo son píxeles.', 'Un nombre avec une unité (40px, 3em) ou auto. Un nombre seul est en pixels.', 'Eine Zahl mit Einheit (40px, 3em) oder auto. Eine Zahl allein bedeutet Pixel.', 'Um número com unidade (40px, 3em) ou auto. Um número sozinho é em pixels.'),
 'Description length (characters)': ('Lunghezza della descrizione (caratteri)', 'Longitud de la descripción (caracteres)', 'Longueur de la description (caractères)', 'Länge der Beschreibung (Zeichen)', 'Tamanho da descrição (caracteres)'),
 'There is no ticker to show.': ('Non c’è nessun ticker da mostrare. Controlla gli indirizzi dei feed.', 'No hay ningún ticker que mostrar. Revisa las direcciones de los feeds.', 'Aucun bandeau à afficher. Vérifiez les adresses des flux.', 'Es gibt keinen Ticker anzuzeigen. Prüfe die Feed-Adressen.', 'Não há nenhum ticker para mostrar. Verifique os endereços dos feeds.'),
 # the block's keywords (block.json, context "block keyword")
 'feed': ('feed', 'feed', 'flux', 'Feed', 'feed'),
 'rss': ('rss', 'rss', 'rss', 'RSS', 'rss'),
 'atom': ('atom', 'atom', 'atom', 'Atom', 'atom'),
 'ticker': ('ticker', 'ticker', 'bandeau', 'Ticker', 'ticker'),
 'news': ('notizie', 'noticias', 'actualités', 'Nachrichten', 'notícias'),
 'marquee': ('scorrimento', 'desplazamiento', 'défilant', 'Laufband', 'rolagem'),
}
SKIP = ('https://github.com/', 'Giovambattista Fazioli', 'https://undolog.com')

def unq(s):
    return bytes(s, 'utf-8').decode('unicode_escape').encode('latin-1').decode('utf-8') if '\\' in s else s

def parse(pot):
    text = open(pot, encoding='utf-8').read()
    blocks = text.split('\n\n')
    head = blocks[0]
    entries = []
    for b in blocks[1:]:
        if 'msgid' not in b: continue
        m = re.search(r'^msgid ((?:".*"\n?)+)', b, re.M)
        raw = m.group(1)
        msgid = ''.join(re.findall(r'"(.*)"', raw))
        # the comments and the context (a block's title and keywords have one): kept as they are
        comments = [l for l in b.split('\n') if l.startswith('#') or l.startswith('msgctxt ')]
        entries.append((comments, msgid))
    return head, entries

def esc(s):
    return s.replace('\\', '\\\\').replace('"', '\\"')

head, entries = parse(POT)
used = set()
for i, loc in enumerate(LOCALES):
    out = []
    h = head.split('\n')
    keep = []
    for l in h:
        if l.startswith('"Language-Team'): l = '"Language-Team: %s\\n"' % LANGNAME[loc]
        if l.startswith('"Last-Translator'): l = '"Last-Translator: Giovambattista Fazioli\\n"'
        if l.startswith('"PO-Revision-Date'): l = '"PO-Revision-Date: 2026-11-26 09:00+0000\\n"'
        if l.startswith('# Copyright') or l.startswith('# This file'): pass
        keep.append(l)
        if l.startswith('"X-Domain'):
            keep.append('"Language: %s\\n"' % loc)
            keep.append('"Plural-Forms: %s\\n"' % PLURAL[loc])
    out.append('\n'.join(keep))
    for comments, msgid in entries:
        tr = ''
        if not msgid.startswith(SKIP):
            hits = [k for k in T if msgid.startswith(k)]
            # longest matching key wins, exact first
            exact = [k for k in hits if k == msgid]
            k = exact[0] if exact else (max(hits, key=len) if hits else None)
            if k is None:
                print('NO TRANSLATION for', repr(msgid)); sys.exit(1)
            used.add(k)
            tr = T[k][i]
        out.append('\n'.join(comments) + '\nmsgid "%s"\nmsgstr "%s"' % (msgid, esc(tr)))
    po = ROOT + '/flash-feed-scroll-reader-%s.po' % loc
    open(po, 'w', encoding='utf-8').write('\n\n'.join(out) + '\n')
    subprocess.run(['msgfmt', '--check', '-o', ROOT + '/flash-feed-scroll-reader-%s.mo' % loc, po], check=True)
print('unused keys:', [k for k in T if k not in used])
