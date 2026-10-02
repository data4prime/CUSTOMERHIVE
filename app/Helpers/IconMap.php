<?php

namespace App\Helpers;

/**
 * Corrispondenza FontAwesome 4 -> Bootstrap Icons.
 *
 * Fonte unica per:
 *  - il CSS di compatibilita' `public/css/ch-icons-compat.css` (generato da
 *    `local-scripts/gen-icons-compat.php`), che fa renderizzare con il font di
 *    Bootstrap Icons anche i nomi `fa fa-*` salvati nel DB dei clienti
 *    (menu, moduli, widget) e usati nei loro controller/viste custom;
 *  - il selettore icone di menu/moduli (vedi Fontawesome::getIcons()), che
 *    ora propone i nomi di Bootstrap Icons.
 *
 * Chiave = nome FA4 senza prefisso `fa-`, valore = nome Bootstrap Icons senza
 * prefisso `bi-`. Un nome FA senza voce qui renderizza l'icona di ripiego
 * (`bi-app`) invece di un riquadro vuoto.
 */
class IconMap
{
    public const FALLBACK = 'app';

    public static function map(): array
    {
        return [
            'grip-vertical' => 'grip-vertical', 'glass' => 'cup-straw', 'music' => 'music-note-beamed', 'search' => 'search',
            'envelope-o' => 'envelope', 'envelope' => 'envelope-fill', 'envelope-square' => 'envelope-fill',
            'heart' => 'heart-fill', 'heart-o' => 'heart', 'star' => 'star-fill', 'star-o' => 'star',
            'star-half' => 'star-half', 'star-half-empty' => 'star-half', 'star-half-full' => 'star-half', 'star-half-o' => 'star-half',
            'user' => 'person-fill', 'film' => 'film', 'th-large' => 'grid-fill', 'th' => 'grid-3x3-gap-fill',
            'th-list' => 'list-ul', 'check' => 'check-lg', 'remove' => 'x-lg', 'close' => 'x-lg', 'times' => 'x-lg',
            'search-plus' => 'zoom-in', 'search-minus' => 'zoom-out', 'power-off' => 'power', 'signal' => 'reception-4',
            'gear' => 'gear-fill', 'cog' => 'gear-fill', 'gears' => 'gear-wide-connected', 'cogs' => 'gear-wide-connected',
            'trash-o' => 'trash', 'trash' => 'trash-fill', 'home' => 'house-fill', 'file-o' => 'file-earmark',
            'clock-o' => 'clock', 'road' => 'sign-turn-right', 'download' => 'download', 'arrow-circle-o-down' => 'arrow-down-circle',
            'arrow-circle-o-up' => 'arrow-up-circle', 'arrow-circle-o-right' => 'arrow-right-circle', 'arrow-circle-o-left' => 'arrow-left-circle',
            'inbox' => 'inbox', 'play-circle-o' => 'play-circle', 'play-circle' => 'play-circle-fill',
            'rotate-right' => 'arrow-clockwise', 'repeat' => 'arrow-repeat', 'refresh' => 'arrow-repeat',
            'rotate-left' => 'arrow-counterclockwise', 'undo' => 'arrow-counterclockwise', 'list-alt' => 'card-list',
            'lock' => 'lock-fill', 'unlock' => 'unlock-fill', 'unlock-alt' => 'unlock', 'flag' => 'flag-fill', 'flag-o' => 'flag',
            'flag-checkered' => 'flag', 'headphones' => 'headphones', 'volume-off' => 'volume-mute', 'volume-down' => 'volume-down',
            'volume-up' => 'volume-up', 'qrcode' => 'qr-code', 'barcode' => 'upc', 'tag' => 'tag-fill', 'tags' => 'tags-fill',
            'book' => 'book', 'bookmark' => 'bookmark-fill', 'bookmark-o' => 'bookmark', 'print' => 'printer',
            'camera' => 'camera-fill', 'camera-retro' => 'camera', 'font' => 'fonts', 'bold' => 'type-bold', 'italic' => 'type-italic',
            'text-height' => 'text-paragraph', 'text-width' => 'text-paragraph', 'align-left' => 'text-left',
            'align-center' => 'text-center', 'align-right' => 'text-right', 'align-justify' => 'justify',
            'list' => 'list-task', 'list-ul' => 'list-ul', 'list-ol' => 'list-ol', 'dedent' => 'text-indent-right',
            'outdent' => 'text-indent-right', 'indent' => 'text-indent-left', 'video-camera' => 'camera-video-fill',
            'photo' => 'image', 'image' => 'image', 'picture-o' => 'image', 'pencil' => 'pencil-fill', 'edit' => 'pencil-square',
            'pencil-square-o' => 'pencil-square', 'pencil-square' => 'pencil-square', 'map-marker' => 'geo-alt-fill',
            'adjust' => 'circle-half', 'tint' => 'droplet-fill', 'share-square-o' => 'box-arrow-up-right',
            'share-square' => 'box-arrow-up-right', 'check-square-o' => 'check-square', 'check-square' => 'check-square-fill',
            'arrows' => 'arrows-move', 'arrows-alt' => 'arrows-fullscreen', 'arrows-v' => 'arrows-vertical',
            'arrows-h' => 'arrows-expand', 'step-backward' => 'skip-start-fill', 'fast-backward' => 'skip-backward-fill',
            'backward' => 'rewind-fill', 'play' => 'play-fill', 'pause' => 'pause-fill', 'stop' => 'stop-fill',
            'forward' => 'fast-forward-fill', 'fast-forward' => 'skip-forward-fill', 'step-forward' => 'skip-end-fill',
            'eject' => 'eject-fill', 'chevron-left' => 'chevron-left', 'chevron-right' => 'chevron-right',
            'chevron-up' => 'chevron-up', 'chevron-down' => 'chevron-down', 'plus-circle' => 'plus-circle-fill',
            'minus-circle' => 'dash-circle-fill', 'times-circle' => 'x-circle-fill', 'check-circle' => 'check-circle-fill',
            'question-circle' => 'question-circle-fill', 'info-circle' => 'info-circle-fill', 'crosshairs' => 'crosshair',
            'times-circle-o' => 'x-circle', 'check-circle-o' => 'check-circle', 'ban' => 'slash-circle',
            'arrow-left' => 'arrow-left', 'arrow-right' => 'arrow-right', 'arrow-up' => 'arrow-up', 'arrow-down' => 'arrow-down',
            'mail-forward' => 'arrow-90deg-right', 'share' => 'share-fill', 'expand' => 'arrows-angle-expand',
            'compress' => 'arrows-angle-contract', 'plus' => 'plus-lg', 'minus' => 'dash-lg', 'asterisk' => 'asterisk',
            'exclamation-circle' => 'exclamation-circle-fill', 'gift' => 'gift-fill', 'leaf' => 'tree', 'fire' => 'fire',
            'eye' => 'eye-fill', 'eye-slash' => 'eye-slash-fill', 'warning' => 'exclamation-triangle-fill',
            'exclamation-triangle' => 'exclamation-triangle-fill', 'triangle-exclamation' => 'exclamation-triangle-fill',
            'plane' => 'airplane-fill', 'calendar' => 'calendar3', 'calendar-o' => 'calendar', 'random' => 'shuffle',
            'comment' => 'chat-fill', 'comment-o' => 'chat', 'comments' => 'chat-dots-fill', 'comments-o' => 'chat-dots',
            'commenting' => 'chat-text-fill', 'commenting-o' => 'chat-text', 'magnet' => 'magnet-fill', 'retweet' => 'arrow-repeat',
            'shopping-cart' => 'cart-fill', 'cart-plus' => 'cart-plus', 'cart-arrow-down' => 'cart-dash',
            'folder' => 'folder-fill', 'folder-o' => 'folder', 'folder-open' => 'folder2-open', 'folder-open-o' => 'folder2-open',
            'bar-chart-o' => 'bar-chart-fill', 'bar-chart' => 'bar-chart-fill', 'area-chart' => 'graph-up',
            'pie-chart' => 'pie-chart-fill', 'line-chart' => 'graph-up',
            'key' => 'key-fill', 'thumbs-o-up' => 'hand-thumbs-up', 'thumbs-o-down' => 'hand-thumbs-down',
            'thumbs-up' => 'hand-thumbs-up-fill', 'thumbs-down' => 'hand-thumbs-down-fill', 'sign-out' => 'box-arrow-right',
            'sign-in' => 'box-arrow-in-right', 'thumb-tack' => 'pin-angle-fill', 'external-link' => 'box-arrow-up-right',
            'external-link-square' => 'box-arrow-up-right', 'trophy' => 'trophy-fill', 'upload' => 'upload',
            'lemon-o' => 'circle', 'phone' => 'telephone-fill', 'phone-square' => 'telephone-fill', 'square-o' => 'square',
            'square' => 'square-fill', 'credit-card' => 'credit-card-fill', 'credit-card-alt' => 'credit-card',
            'feed' => 'rss-fill', 'rss' => 'rss-fill', 'rss-square' => 'rss-fill', 'hdd-o' => 'hdd', 'bullhorn' => 'megaphone-fill',
            'bell' => 'bell-fill', 'bell-o' => 'bell', 'bell-slash' => 'bell-slash-fill', 'bell-slash-o' => 'bell-slash',
            'certificate' => 'patch-check-fill', 'hand-o-right' => 'hand-index', 'hand-o-left' => 'hand-index',
            'hand-o-up' => 'hand-index', 'hand-o-down' => 'hand-index', 'hand-pointer-o' => 'hand-index',
            'arrow-circle-left' => 'arrow-left-circle-fill', 'arrow-circle-right' => 'arrow-right-circle-fill',
            'arrow-circle-up' => 'arrow-up-circle-fill', 'arrow-circle-down' => 'arrow-down-circle-fill',
            'globe' => 'globe', 'wrench' => 'wrench', 'tasks' => 'list-check', 'filter' => 'funnel-fill',
            'briefcase' => 'briefcase-fill', 'group' => 'people-fill', 'users' => 'people-fill', 'chain' => 'link-45deg',
            'link' => 'link-45deg', 'cloud' => 'cloud-fill', 'flask' => 'beaker', 'cut' => 'scissors', 'scissors' => 'scissors',
            'copy' => 'files', 'files-o' => 'files', 'paperclip' => 'paperclip', 'save' => 'floppy-fill', 'floppy-o' => 'floppy',
            'navicon' => 'list', 'reorder' => 'list', 'bars' => 'list', 'strikethrough' => 'type-strikethrough',
            'underline' => 'type-underline', 'table' => 'table', 'magic' => 'magic', 'truck' => 'truck',
            'money' => 'cash-stack', 'caret-down' => 'caret-down-fill', 'caret-up' => 'caret-up-fill',
            'caret-left' => 'caret-left-fill', 'caret-right' => 'caret-right-fill', 'columns' => 'layout-three-columns',
            'unsorted' => 'arrow-down-up', 'sort' => 'arrow-down-up', 'sort-down' => 'sort-down', 'sort-desc' => 'sort-down',
            'sort-up' => 'sort-up', 'sort-asc' => 'sort-up', 'sort-alpha-asc' => 'sort-alpha-down',
            'sort-alpha-desc' => 'sort-alpha-up', 'sort-amount-asc' => 'sort-down-alt', 'sort-amount-desc' => 'sort-up-alt',
            'sort-numeric-asc' => 'sort-numeric-down', 'sort-numeric-desc' => 'sort-numeric-up',
            'legal' => 'hammer', 'gavel' => 'hammer', 'dashboard' => 'speedometer2', 'tachometer' => 'speedometer2',
            'flash' => 'lightning-charge-fill', 'bolt' => 'lightning-charge-fill', 'sitemap' => 'diagram-3-fill',
            'umbrella' => 'umbrella-fill', 'paste' => 'clipboard', 'clipboard' => 'clipboard', 'lightbulb-o' => 'lightbulb',
            'exchange' => 'arrow-left-right', 'cloud-download' => 'cloud-download', 'cloud-upload' => 'cloud-upload',
            'user-md' => 'person-badge', 'stethoscope' => 'heart-pulse', 'suitcase' => 'briefcase', 'coffee' => 'cup-hot-fill',
            'cutlery' => 'cup-straw', 'file-text-o' => 'file-earmark-text', 'file-text' => 'file-earmark-text-fill',
            'file' => 'file-earmark-fill', 'building-o' => 'building', 'building' => 'building-fill', 'hospital-o' => 'hospital',
            'ambulance' => 'truck', 'medkit' => 'bandaid-fill', 'fighter-jet' => 'airplane', 'beer' => 'cup-straw',
            'h-square' => 'hospital', 'plus-square' => 'plus-square-fill', 'plus-square-o' => 'plus-square',
            'minus-square' => 'dash-square-fill', 'minus-square-o' => 'dash-square',
            'angle-double-left' => 'chevron-double-left', 'angle-double-right' => 'chevron-double-right',
            'angle-double-up' => 'chevron-double-up', 'angle-double-down' => 'chevron-double-down',
            'angle-left' => 'chevron-left', 'angle-right' => 'chevron-right', 'angle-up' => 'chevron-up',
            'angle-down' => 'chevron-down', 'desktop' => 'display', 'laptop' => 'laptop', 'tablet' => 'tablet',
            'mobile-phone' => 'phone', 'mobile' => 'phone', 'circle-o' => 'circle', 'circle' => 'circle-fill',
            'circle-thin' => 'circle', 'circle-o-notch' => 'arrow-repeat', 'dot-circle-o' => 'record-circle',
            'quote-left' => 'quote', 'quote-right' => 'quote', 'spinner' => 'arrow-repeat', 'mail-reply' => 'reply-fill',
            'reply' => 'reply-fill', 'mail-reply-all' => 'reply-all-fill', 'reply-all' => 'reply-all-fill',
            'smile-o' => 'emoji-smile', 'frown-o' => 'emoji-frown', 'meh-o' => 'emoji-neutral', 'gamepad' => 'controller',
            'keyboard-o' => 'keyboard', 'terminal' => 'terminal-fill', 'code' => 'code-slash', 'location-arrow' => 'cursor-fill',
            'crop' => 'crop', 'code-fork' => 'diagram-2', 'unlink' => 'link-45deg', 'chain-broken' => 'link-45deg',
            'question' => 'question-lg', 'info' => 'info-lg', 'exclamation' => 'exclamation-lg',
            'superscript' => 'superscript', 'subscript' => 'subscript', 'eraser' => 'eraser-fill',
            'puzzle-piece' => 'puzzle-fill', 'microphone' => 'mic-fill', 'microphone-slash' => 'mic-mute-fill',
            'shield' => 'shield-fill', 'fire-extinguisher' => 'fire', 'rocket' => 'rocket-takeoff-fill',
            'chevron-circle-left' => 'chevron-left', 'chevron-circle-right' => 'chevron-right',
            'chevron-circle-up' => 'chevron-up', 'chevron-circle-down' => 'chevron-down', 'anchor' => 'tsunami',
            'bullseye' => 'bullseye', 'ellipsis-h' => 'three-dots', 'ellipsis-v' => 'three-dots-vertical',
            'ticket' => 'ticket-perforated-fill', 'level-up' => 'arrow-up', 'level-down' => 'arrow-down',
            'pencil-square' => 'pencil-square', 'compass' => 'compass-fill', 'toggle-down' => 'caret-down-square',
            'caret-square-o-down' => 'caret-down-square', 'toggle-up' => 'caret-up-square', 'caret-square-o-up' => 'caret-up-square',
            'toggle-right' => 'caret-right-square', 'caret-square-o-right' => 'caret-right-square',
            'toggle-left' => 'caret-left-square', 'caret-square-o-left' => 'caret-left-square',
            'euro' => 'currency-euro', 'eur' => 'currency-euro', 'gbp' => 'currency-pound', 'dollar' => 'currency-dollar',
            'usd' => 'currency-dollar', 'rupee' => 'currency-rupee', 'inr' => 'currency-rupee', 'cny' => 'currency-yen',
            'rmb' => 'currency-yen', 'yen' => 'currency-yen', 'jpy' => 'currency-yen', 'ruble' => 'cash', 'rouble' => 'cash',
            'rub' => 'cash', 'won' => 'cash', 'krw' => 'cash', 'bitcoin' => 'currency-bitcoin', 'btc' => 'currency-bitcoin',
            'turkish-lira' => 'cash', 'try' => 'cash', 'shekel' => 'cash', 'sheqel' => 'cash', 'ils' => 'cash',
            'youtube-square' => 'youtube', 'youtube' => 'youtube', 'youtube-play' => 'youtube', 'xing' => 'person-lines-fill',
            'xing-square' => 'person-lines-fill', 'dropbox' => 'dropbox', 'stack-overflow' => 'stack-overflow',
            'instagram' => 'instagram', 'flickr' => 'camera', 'adn' => 'app', 'bitbucket' => 'bucket',
            'bitbucket-square' => 'bucket', 'tumblr' => 'app', 'tumblr-square' => 'app',
            'long-arrow-down' => 'arrow-down', 'long-arrow-up' => 'arrow-up', 'long-arrow-left' => 'arrow-left',
            'long-arrow-right' => 'arrow-right', 'apple' => 'apple', 'windows' => 'windows', 'android' => 'android2',
            'linux' => 'ubuntu', 'dribbble' => 'dribbble', 'skype' => 'skype', 'foursquare' => 'geo-alt',
            'trello' => 'trello', 'female' => 'gender-female', 'male' => 'gender-male', 'gittip' => 'heart',
            'gratipay' => 'heart', 'sun-o' => 'sun', 'moon-o' => 'moon', 'archive' => 'archive-fill', 'bug' => 'bug-fill',
            'vk' => 'app', 'weibo' => 'app', 'renren' => 'app', 'pagelines' => 'tree', 'stack-exchange' => 'stack',
            'wheelchair' => 'person-wheelchair', 'vimeo-square' => 'vimeo', 'vimeo' => 'vimeo', 'space-shuttle' => 'rocket-takeoff',
            'slack' => 'slack', 'wordpress' => 'wordpress', 'openid' => 'app', 'institution' => 'bank', 'bank' => 'bank',
            'university' => 'bank', 'mortar-board' => 'mortarboard-fill', 'graduation-cap' => 'mortarboard-fill',
            'yahoo' => 'app', 'google' => 'google', 'google-plus' => 'google', 'google-plus-square' => 'google',
            'reddit' => 'reddit', 'reddit-square' => 'reddit', 'reddit-alien' => 'reddit', 'stumbleupon-circle' => 'app',
            'stumbleupon' => 'app', 'delicious' => 'app', 'digg' => 'app', 'pied-piper' => 'app', 'pied-piper-alt' => 'app',
            'drupal' => 'app', 'joomla' => 'app', 'language' => 'translate', 'fax' => 'printer', 'child' => 'person-standing',
            'paw' => 'app', 'spoon' => 'cup-straw', 'cube' => 'box', 'cubes' => 'boxes', 'behance' => 'behance',
            'behance-square' => 'behance', 'steam' => 'steam', 'steam-square' => 'steam', 'recycle' => 'recycle',
            'automobile' => 'car-front-fill', 'car' => 'car-front-fill', 'cab' => 'taxi-front-fill', 'taxi' => 'taxi-front-fill',
            'tree' => 'tree-fill', 'spotify' => 'spotify', 'deviantart' => 'app', 'soundcloud' => 'soundwave',
            'database' => 'database-fill', 'file-pdf-o' => 'file-earmark-pdf', 'file-word-o' => 'file-earmark-word',
            'file-excel-o' => 'file-earmark-excel', 'file-powerpoint-o' => 'file-earmark-ppt',
            'file-photo-o' => 'file-earmark-image', 'file-picture-o' => 'file-earmark-image', 'file-image-o' => 'file-earmark-image',
            'file-zip-o' => 'file-earmark-zip', 'file-archive-o' => 'file-earmark-zip', 'file-sound-o' => 'file-earmark-music',
            'file-audio-o' => 'file-earmark-music', 'file-movie-o' => 'file-earmark-play', 'file-video-o' => 'file-earmark-play',
            'file-code-o' => 'file-earmark-code', 'vine' => 'app', 'codepen' => 'app', 'jsfiddle' => 'app',
            'life-bouy' => 'life-preserver', 'life-buoy' => 'life-preserver', 'life-saver' => 'life-preserver',
            'support' => 'life-preserver', 'life-ring' => 'life-preserver', 'ra' => 'app', 'rebel' => 'app', 'ge' => 'app',
            'empire' => 'app', 'git-square' => 'git', 'git' => 'git', 'y-combinator-square' => 'app', 'yc-square' => 'app',
            'y-combinator' => 'app', 'yc' => 'app', 'hacker-news' => 'app', 'tencent-weibo' => 'app', 'qq' => 'app',
            'wechat' => 'wechat', 'weixin' => 'wechat', 'send' => 'send-fill', 'paper-plane' => 'send-fill',
            'send-o' => 'send', 'paper-plane-o' => 'send', 'history' => 'clock-history', 'header' => 'type-h1',
            'paragraph' => 'paragraph', 'sliders' => 'sliders', 'share-alt' => 'share-fill', 'share-alt-square' => 'share-fill',
            'bomb' => 'app', 'soccer-ball-o' => 'dribbble', 'futbol-o' => 'dribbble', 'tty' => 'telephone',
            'binoculars' => 'binoculars-fill', 'plug' => 'plug-fill', 'slideshare' => 'app', 'twitch' => 'twitch',
            'yelp' => 'app', 'newspaper-o' => 'newspaper', 'wifi' => 'wifi', 'calculator' => 'calculator-fill',
            'paypal' => 'paypal', 'google-wallet' => 'wallet2', 'cc-visa' => 'credit-card', 'cc-mastercard' => 'credit-card',
            'cc-discover' => 'credit-card', 'cc-amex' => 'credit-card', 'cc-paypal' => 'paypal', 'cc-stripe' => 'stripe',
            'cc-jcb' => 'credit-card', 'cc-diners-club' => 'credit-card', 'cc' => 'credit-card',
            'copyright' => 'c-circle', 'at' => 'at', 'eyedropper' => 'eyedropper', 'paint-brush' => 'brush-fill',
            'birthday-cake' => 'cake2-fill', 'lastfm' => 'app', 'lastfm-square' => 'app', 'toggle-off' => 'toggle-off',
            'toggle-on' => 'toggle-on', 'bicycle' => 'bicycle', 'bus' => 'bus-front-fill', 'ioxhost' => 'app',
            'angellist' => 'app', 'meanpath' => 'app', 'buysellads' => 'app', 'connectdevelop' => 'app', 'dashcube' => 'app',
            'forumbee' => 'app', 'leanpub' => 'app', 'sellsy' => 'app', 'shirtsinbulk' => 'app', 'simplybuilt' => 'app',
            'skyatlas' => 'app', 'diamond' => 'gem', 'ship' => 'tsunami', 'user-secret' => 'person-fill-lock',
            'motorcycle' => 'bicycle', 'street-view' => 'person-walking', 'heartbeat' => 'heart-pulse-fill',
            'venus' => 'gender-female', 'mars' => 'gender-male', 'mercury' => 'gender-ambiguous', 'intersex' => 'gender-ambiguous',
            'transgender' => 'gender-trans', 'transgender-alt' => 'gender-trans', 'venus-double' => 'gender-female',
            'mars-double' => 'gender-male', 'venus-mars' => 'gender-ambiguous', 'mars-stroke' => 'gender-male',
            'mars-stroke-v' => 'gender-male', 'mars-stroke-h' => 'gender-male', 'neuter' => 'gender-neuter',
            'genderless' => 'gender-neuter', 'facebook-official' => 'facebook', 'facebook-square' => 'facebook',
            'facebook-f' => 'facebook', 'facebook' => 'facebook', 'pinterest' => 'pinterest', 'pinterest-square' => 'pinterest',
            'pinterest-p' => 'pinterest', 'twitter' => 'twitter-x', 'twitter-square' => 'twitter-x', 'linkedin' => 'linkedin',
            'linkedin-square' => 'linkedin', 'github-square' => 'github', 'github' => 'github', 'github-alt' => 'github',
            'whatsapp' => 'whatsapp', 'server' => 'hdd-rack-fill', 'user-plus' => 'person-plus-fill',
            'user-times' => 'person-x-fill', 'hotel' => 'building', 'bed' => 'lamp', 'viacoin' => 'currency-bitcoin',
            'train' => 'train-front-fill', 'subway' => 'train-front', 'medium' => 'medium', 'optin-monster' => 'app',
            'opencart' => 'cart', 'expeditedssl' => 'shield-lock-fill', 'battery-4' => 'battery-full',
            'battery-full' => 'battery-full', 'battery-3' => 'battery-half', 'battery-three-quarters' => 'battery-half',
            'battery-2' => 'battery-half', 'battery-half' => 'battery-half', 'battery-1' => 'battery',
            'battery-quarter' => 'battery', 'battery-0' => 'battery', 'battery-empty' => 'battery',
            'mouse-pointer' => 'cursor-fill', 'i-cursor' => 'cursor-text', 'object-group' => 'collection',
            'object-ungroup' => 'collection', 'sticky-note' => 'sticky-fill', 'sticky-note-o' => 'sticky', 'clone' => 'copy',
            'balance-scale' => 'bar-chart-steps', 'hourglass-o' => 'hourglass', 'hourglass-1' => 'hourglass-top',
            'hourglass-start' => 'hourglass-top', 'hourglass-2' => 'hourglass-split', 'hourglass-half' => 'hourglass-split',
            'hourglass-3' => 'hourglass-bottom', 'hourglass-end' => 'hourglass-bottom', 'hourglass' => 'hourglass',
            'hand-grab-o' => 'hand-index', 'hand-rock-o' => 'hand-index', 'hand-stop-o' => 'hand-index',
            'hand-paper-o' => 'hand-index', 'hand-scissors-o' => 'hand-index', 'hand-lizard-o' => 'hand-index',
            'hand-spock-o' => 'hand-index', 'hand-peace-o' => 'hand-index', 'trademark' => 'app', 'registered' => 'app',
            'creative-commons' => 'app', 'gg' => 'app', 'gg-circle' => 'app', 'tripadvisor' => 'app',
            'odnoklassniki' => 'app', 'odnoklassniki-square' => 'app', 'get-pocket' => 'app', 'wikipedia-w' => 'app',
            'safari' => 'browser-safari', 'chrome' => 'browser-chrome', 'firefox' => 'browser-firefox',
            'opera' => 'app', 'internet-explorer' => 'app', 'tv' => 'tv-fill', 'television' => 'tv-fill',
            'contao' => 'app', '500px' => 'app', 'amazon' => 'amazon', 'calendar-plus-o' => 'calendar-plus',
            'calendar-minus-o' => 'calendar-minus', 'calendar-times-o' => 'calendar-x', 'calendar-check-o' => 'calendar-check',
            'industry' => 'buildings-fill', 'map-pin' => 'geo-fill', 'map-signs' => 'signpost-split-fill', 'map-o' => 'map',
            'map' => 'map-fill', 'houzz' => 'app', 'black-tie' => 'app', 'fonticons' => 'app', 'edge' => 'browser-edge',
            'codiepie' => 'app', 'modx' => 'app', 'fort-awesome' => 'app', 'usb' => 'usb-symbol', 'product-hunt' => 'app',
            'mixcloud' => 'app', 'scribd' => 'app', 'pause-circle' => 'pause-circle-fill', 'pause-circle-o' => 'pause-circle',
            'stop-circle' => 'stop-circle-fill', 'stop-circle-o' => 'stop-circle', 'shopping-bag' => 'bag-fill',
            'shopping-basket' => 'basket-fill', 'hashtag' => 'hash', 'bluetooth' => 'bluetooth', 'bluetooth-b' => 'bluetooth',
            'percent' => 'percent', 'maxcdn' => 'app', 'html5' => 'filetype-html', 'css3' => 'filetype-css',
        ];
    }

    /**
     * Nomi Lucide (salvati nella config del widget Small Box dei dashboard
     * gia' creati) -> nome Bootstrap Icons. I nomi non elencati sono gia'
     * validi cosi' o sono nomi Bootstrap Icons scelti dopo la migrazione.
     */
    public static function lucideMap(): array
    {
        return [
            'arrow-down-a-z' => 'sort-alpha-down', 'arrow-up-down' => 'arrow-down-up', 'arrow-up-narrow-wide' => 'sort-numeric-up',
            'ban' => 'slash-circle', 'calendar' => 'calendar3', 'chart-area' => 'graph-up', 'check' => 'check-lg',
            'circle-alert' => 'exclamation-circle', 'circle-check' => 'check-circle', 'circle-help' => 'question-circle',
            'circle-plus' => 'plus-circle', 'columns-3' => 'layout-three-columns', 'factory' => 'buildings',
            'file' => 'file-earmark', 'file-spreadsheet' => 'file-earmark-spreadsheet', 'file-text' => 'file-earmark-text',
            'filter' => 'funnel', 'flame' => 'fire', 'info' => 'info-circle', 'layout-dashboard' => 'grid-1x2',
            'layout-grid' => 'grid', 'list' => 'list-ul', 'log-out' => 'box-arrow-right', 'mail' => 'envelope',
            'map-pin' => 'geo-alt', 'menu' => 'list', 'message-square' => 'chat-square', 'minus' => 'dash-lg',
            'move' => 'arrows-move', 'plus' => 'plus-lg', 'refresh-cw' => 'arrow-repeat', 'save' => 'floppy',
            'settings' => 'gear', 'settings-2' => 'sliders', 'shopping-cart' => 'cart', 'signal' => 'reception-4',
            'sliders-horizontal' => 'sliders', 'square-check' => 'check-square', 'square-minus' => 'dash-square',
            'square-pen' => 'pencil-square', 'square-plus' => 'plus-square', 'trash-2' => 'trash',
            'triangle-alert' => 'exclamation-triangle', 'user' => 'person', 'users' => 'people', 'x' => 'x-lg',
        ];
    }

    /**
     * Classe Bootstrap Icons da un nome "nudo" (es. 'check', 'trash-o') come
     * quello che i controller custom passano in button_selected/index_button
     * (prima si anteponeva "fa fa-"). Un nome FontAwesome noto viene tradotto,
     * un nome gia' valido per Bootstrap Icons resta com'e'.
     */
    public static function biClass(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        if (preg_match('/^(fa|bi)\s/', $name)) {
            return self::toBi($name);
        }
        $map = self::map();

        return 'bi bi-' . ($map[$name] ?? $name);
    }

    /**
     * Converte una classe icona salvata (es. "fa fa-cog", "fa\n  fa-cog") in
     * quella equivalente di Bootstrap Icons ("bi bi-gear-fill"). Se e' gia'
     * una classe `bi` o non e' FontAwesome viene restituita invariata.
     */
    public static function toBi(?string $class): string
    {
        $class = trim((string) $class);
        if ($class === '' || !preg_match('/\bfa-([a-z0-9-]+)/', $class, $m)) {
            return $class;
        }
        $map = self::map();

        return 'bi bi-' . ($map[$m[1]] ?? self::FALLBACK);
    }
}
