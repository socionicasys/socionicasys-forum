<?php
/**
 * CSSTidy - CSS Parser and Optimiser
 *
 * CSS Parser class
 *
 * Copyright 2005, 2006, 2007 Florian Schmitz
 *
 * This file is part of CSSTidy.
 *
 *   CSSTidy is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or
 *   (at your option) any later version.
 *
 *   CSSTidy is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 * 
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
 * @package csstidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2007
 * @author Brett Zamir (brettz9 at yahoo dot com) 2007
 * @author Nikolay Matsievsky (speed at webo dot name) 2009-2010
 */

/**
 * Defines ctype functions if required
 *
 * @version 1.0
 */
    if (!function_exists('ctype_space')) {  function ctype_space($text) {  return !preg_match("/[^\s\r\n\t\f]/", $text);  } }  if (!function_exists('ctype_alpha')) {  function ctype_alpha($text) {  return preg_match("/[a-zA-Z]/", $text);  } }  if (!function_exists("ctype_xdigit")){  function ctype_xdigit($string = ''){  return !strlen(trim($string, '1234567890abcdefABCDEF'));  } }  

/**
 * Various CSS data needed for correct optimisations etc.
 *
 * @version 1.3
 */
   define('AT_START', 1); define('AT_END', 2); define('SEL_START', 3); define('SEL_END', 4); define('PROPERTY', 5); define('VALUE', 6); define('COMMENT', 7); define('DEFAULT_AT', 41);   $GLOBALS['csstidy']['whitespace'] = array(' ',"\n","\t","\r","\x0B");   $GLOBALS['csstidy']['tokens'] = '/@}{;:=\'"(,\\!$%&)*+.<>?[]^`|~';   $GLOBALS['csstidy']['units'] = array('in','cm','mm','pt','pc','px','rem','em','%','ex','gd','vw','vh','vm','deg','grad','rad','ms','s','khz','hz');   $GLOBALS['csstidy']['at_rules'] = array('page' => 'is','font-face' => 'is','charset' => 'iv', 'import' => 'iv','namespace' => 'iv','media' => 'at');    $GLOBALS['csstidy']['unit_values'] = array ('background', 'background-position', 'border', 'border-top', 'border-right', 'border-bottom', 'border-left', 'border-width',  'border-top-width', 'border-right-width', 'border-left-width', 'border-bottom-width', 'bottom', 'border-spacing',  'font-size', 'height', 'left', 'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left', 'max-height',  'max-width', 'min-height', 'min-width', 'outline', 'outline-width', 'padding', 'padding-top', 'padding-right',   'padding-bottom', 'padding-left', 'right', 'top', 'text-indent', 'letter-spacing', 'word-spacing', 'width');   $GLOBALS['csstidy']['color_values'] = array(); $GLOBALS['csstidy']['color_values'][] = 'background-color'; $GLOBALS['csstidy']['color_values'][] = 'border-color'; $GLOBALS['csstidy']['color_values'][] = 'border-top-color'; $GLOBALS['csstidy']['color_values'][] = 'border-right-color'; $GLOBALS['csstidy']['color_values'][] = 'border-bottom-color'; $GLOBALS['csstidy']['color_values'][] = 'border-left-color'; $GLOBALS['csstidy']['color_values'][] = 'color'; $GLOBALS['csstidy']['color_values'][] = 'outline-color';   $GLOBALS['csstidy']['background_prop_default'] = array(); $GLOBALS['csstidy']['background_prop_default']['background-image'] = 'none'; $GLOBALS['csstidy']['background_prop_default']['background-size'] = 'auto'; $GLOBALS['csstidy']['background_prop_default']['background-repeat'] = 'repeat'; $GLOBALS['csstidy']['background_prop_default']['background-position'] = '0 0'; $GLOBALS['csstidy']['background_prop_default']['background-attachment'] = 'scroll'; $GLOBALS['csstidy']['background_prop_default']['background-clip'] = 'border'; $GLOBALS['csstidy']['background_prop_default']['background-origin'] = 'padding'; $GLOBALS['csstidy']['background_prop_default']['background-color'] = 'transparent';   $GLOBALS['csstidy']['font_prop_default'] = array(); $GLOBALS['csstidy']['font_prop_default']['font-style'] = 'normal'; $GLOBALS['csstidy']['font_prop_default']['font-variant'] = 'normal'; $GLOBALS['csstidy']['font_prop_default']['font-weight'] = 'normal'; $GLOBALS['csstidy']['font_prop_default']['font-size'] = ''; $GLOBALS['csstidy']['font_prop_default']['line-height'] = ''; $GLOBALS['csstidy']['font_prop_default']['font-family'] = '';   $GLOBALS['csstidy']['replace_colors'] = array(); $GLOBALS['csstidy']['replace_colors']['aliceblue'] = '#f0f8ff'; $GLOBALS['csstidy']['replace_colors']['antiquewhite'] = '#faebd7'; $GLOBALS['csstidy']['replace_colors']['aquamarine'] = '#7fffd4'; $GLOBALS['csstidy']['replace_colors']['azure'] = '#f0ffff'; $GLOBALS['csstidy']['replace_colors']['beige'] = '#f5f5dc'; $GLOBALS['csstidy']['replace_colors']['bisque'] = '#ffe4c4'; $GLOBALS['csstidy']['replace_colors']['blanchedalmond'] = '#ffebcd'; $GLOBALS['csstidy']['replace_colors']['blueviolet'] = '#8a2be2'; $GLOBALS['csstidy']['replace_colors']['brown'] = '#a52a2a'; $GLOBALS['csstidy']['replace_colors']['burlywood'] = '#deb887'; $GLOBALS['csstidy']['replace_colors']['cadetblue'] = '#5f9ea0'; $GLOBALS['csstidy']['replace_colors']['chartreuse'] = '#7fff00'; $GLOBALS['csstidy']['replace_colors']['chocolate'] = '#d2691e'; $GLOBALS['csstidy']['replace_colors']['coral'] = '#ff7f50'; $GLOBALS['csstidy']['replace_colors']['cornflowerblue'] = '#6495ed'; $GLOBALS['csstidy']['replace_colors']['cornsilk'] = '#fff8dc'; $GLOBALS['csstidy']['replace_colors']['crimson'] = '#dc143c'; $GLOBALS['csstidy']['replace_colors']['cyan'] = '#00ffff'; $GLOBALS['csstidy']['replace_colors']['darkblue'] = '#00008b'; $GLOBALS['csstidy']['replace_colors']['darkcyan'] = '#008b8b'; $GLOBALS['csstidy']['replace_colors']['darkgoldenrod'] = '#b8860b'; $GLOBALS['csstidy']['replace_colors']['darkgray'] = '#a9a9a9'; $GLOBALS['csstidy']['replace_colors']['darkgreen'] = '#006400'; $GLOBALS['csstidy']['replace_colors']['darkkhaki'] = '#bdb76b'; $GLOBALS['csstidy']['replace_colors']['darkmagenta'] = '#8b008b'; $GLOBALS['csstidy']['replace_colors']['darkolivegreen'] = '#556b2f'; $GLOBALS['csstidy']['replace_colors']['darkorange'] = '#ff8c00'; $GLOBALS['csstidy']['replace_colors']['darkorchid'] = '#9932cc'; $GLOBALS['csstidy']['replace_colors']['darkred'] = '#8b0000'; $GLOBALS['csstidy']['replace_colors']['darksalmon'] = '#e9967a'; $GLOBALS['csstidy']['replace_colors']['darkseagreen'] = '#8fbc8f'; $GLOBALS['csstidy']['replace_colors']['darkslateblue'] = '#483d8b'; $GLOBALS['csstidy']['replace_colors']['darkslategray'] = '#2f4f4f'; $GLOBALS['csstidy']['replace_colors']['darkturquoise'] = '#00ced1'; $GLOBALS['csstidy']['replace_colors']['darkviolet'] = '#9400d3'; $GLOBALS['csstidy']['replace_colors']['deeppink'] = '#ff1493'; $GLOBALS['csstidy']['replace_colors']['deepskyblue'] = '#00bfff'; $GLOBALS['csstidy']['replace_colors']['dimgray'] = '#696969'; $GLOBALS['csstidy']['replace_colors']['dodgerblue'] = '#1e90ff'; $GLOBALS['csstidy']['replace_colors']['feldspar'] = '#d19275'; $GLOBALS['csstidy']['replace_colors']['firebrick'] = '#b22222'; $GLOBALS['csstidy']['replace_colors']['floralwhite'] = '#fffaf0'; $GLOBALS['csstidy']['replace_colors']['forestgreen'] = '#228b22'; $GLOBALS['csstidy']['replace_colors']['gainsboro'] = '#dcdcdc'; $GLOBALS['csstidy']['replace_colors']['ghostwhite'] = '#f8f8ff'; $GLOBALS['csstidy']['replace_colors']['gold'] = '#ffd700'; $GLOBALS['csstidy']['replace_colors']['goldenrod'] = '#daa520'; $GLOBALS['csstidy']['replace_colors']['greenyellow'] = '#adff2f'; $GLOBALS['csstidy']['replace_colors']['honeydew'] = '#f0fff0'; $GLOBALS['csstidy']['replace_colors']['hotpink'] = '#ff69b4'; $GLOBALS['csstidy']['replace_colors']['indianred'] = '#cd5c5c'; $GLOBALS['csstidy']['replace_colors']['indigo'] = '#4b0082'; $GLOBALS['csstidy']['replace_colors']['ivory'] = '#fffff0'; $GLOBALS['csstidy']['replace_colors']['khaki'] = '#f0e68c'; $GLOBALS['csstidy']['replace_colors']['lavender'] = '#e6e6fa'; $GLOBALS['csstidy']['replace_colors']['lavenderblush'] = '#fff0f5'; $GLOBALS['csstidy']['replace_colors']['lawngreen'] = '#7cfc00'; $GLOBALS['csstidy']['replace_colors']['lemonchiffon'] = '#fffacd'; $GLOBALS['csstidy']['replace_colors']['lightblue'] = '#add8e6'; $GLOBALS['csstidy']['replace_colors']['lightcoral'] = '#f08080'; $GLOBALS['csstidy']['replace_colors']['lightcyan'] = '#e0ffff'; $GLOBALS['csstidy']['replace_colors']['lightgoldenrodyellow'] = '#fafad2'; $GLOBALS['csstidy']['replace_colors']['lightgrey'] = '#d3d3d3'; $GLOBALS['csstidy']['replace_colors']['lightgreen'] = '#90ee90'; $GLOBALS['csstidy']['replace_colors']['lightpink'] = '#ffb6c1'; $GLOBALS['csstidy']['replace_colors']['lightsalmon'] = '#ffa07a'; $GLOBALS['csstidy']['replace_colors']['lightseagreen'] = '#20b2aa'; $GLOBALS['csstidy']['replace_colors']['lightskyblue'] = '#87cefa'; $GLOBALS['csstidy']['replace_colors']['lightslateblue'] = '#8470ff'; $GLOBALS['csstidy']['replace_colors']['lightslategray'] = '#778899'; $GLOBALS['csstidy']['replace_colors']['lightsteelblue'] = '#b0c4de'; $GLOBALS['csstidy']['replace_colors']['lightyellow'] = '#ffffe0'; $GLOBALS['csstidy']['replace_colors']['limegreen'] = '#32cd32'; $GLOBALS['csstidy']['replace_colors']['linen'] = '#faf0e6'; $GLOBALS['csstidy']['replace_colors']['magenta'] = '#ff00ff'; $GLOBALS['csstidy']['replace_colors']['mediumaquamarine'] = '#66cdaa'; $GLOBALS['csstidy']['replace_colors']['mediumblue'] = '#0000cd'; $GLOBALS['csstidy']['replace_colors']['mediumorchid'] = '#ba55d3'; $GLOBALS['csstidy']['replace_colors']['mediumpurple'] = '#9370d8'; $GLOBALS['csstidy']['replace_colors']['mediumseagreen'] = '#3cb371'; $GLOBALS['csstidy']['replace_colors']['mediumslateblue'] = '#7b68ee'; $GLOBALS['csstidy']['replace_colors']['mediumspringgreen'] = '#00fa9a'; $GLOBALS['csstidy']['replace_colors']['mediumturquoise'] = '#48d1cc'; $GLOBALS['csstidy']['replace_colors']['mediumvioletred'] = '#c71585'; $GLOBALS['csstidy']['replace_colors']['midnightblue'] = '#191970'; $GLOBALS['csstidy']['replace_colors']['mintcream'] = '#f5fffa'; $GLOBALS['csstidy']['replace_colors']['mistyrose'] = '#ffe4e1'; $GLOBALS['csstidy']['replace_colors']['moccasin'] = '#ffe4b5'; $GLOBALS['csstidy']['replace_colors']['navajowhite'] = '#ffdead'; $GLOBALS['csstidy']['replace_colors']['oldlace'] = '#fdf5e6'; $GLOBALS['csstidy']['replace_colors']['olivedrab'] = '#6b8e23'; $GLOBALS['csstidy']['replace_colors']['orangered'] = '#ff4500'; $GLOBALS['csstidy']['replace_colors']['orchid'] = '#da70d6'; $GLOBALS['csstidy']['replace_colors']['palegoldenrod'] = '#eee8aa'; $GLOBALS['csstidy']['replace_colors']['palegreen'] = '#98fb98'; $GLOBALS['csstidy']['replace_colors']['paleturquoise'] = '#afeeee'; $GLOBALS['csstidy']['replace_colors']['palevioletred'] = '#d87093'; $GLOBALS['csstidy']['replace_colors']['papayawhip'] = '#ffefd5'; $GLOBALS['csstidy']['replace_colors']['peachpuff'] = '#ffdab9'; $GLOBALS['csstidy']['replace_colors']['peru'] = '#cd853f'; $GLOBALS['csstidy']['replace_colors']['pink'] = '#ffc0cb'; $GLOBALS['csstidy']['replace_colors']['plum'] = '#dda0dd'; $GLOBALS['csstidy']['replace_colors']['powderblue'] = '#b0e0e6'; $GLOBALS['csstidy']['replace_colors']['rosybrown'] = '#bc8f8f'; $GLOBALS['csstidy']['replace_colors']['royalblue'] = '#4169e1'; $GLOBALS['csstidy']['replace_colors']['saddlebrown'] = '#8b4513'; $GLOBALS['csstidy']['replace_colors']['salmon'] = '#fa8072'; $GLOBALS['csstidy']['replace_colors']['sandybrown'] = '#f4a460'; $GLOBALS['csstidy']['replace_colors']['seagreen'] = '#2e8b57'; $GLOBALS['csstidy']['replace_colors']['seashell'] = '#fff5ee'; $GLOBALS['csstidy']['replace_colors']['sienna'] = '#a0522d'; $GLOBALS['csstidy']['replace_colors']['skyblue'] = '#87ceeb'; $GLOBALS['csstidy']['replace_colors']['slateblue'] = '#6a5acd'; $GLOBALS['csstidy']['replace_colors']['slategray'] = '#708090'; $GLOBALS['csstidy']['replace_colors']['snow'] = '#fffafa'; $GLOBALS['csstidy']['replace_colors']['springgreen'] = '#00ff7f'; $GLOBALS['csstidy']['replace_colors']['steelblue'] = '#4682b4'; $GLOBALS['csstidy']['replace_colors']['tan'] = '#d2b48c'; $GLOBALS['csstidy']['replace_colors']['thistle'] = '#d8bfd8'; $GLOBALS['csstidy']['replace_colors']['tomato'] = '#ff6347'; $GLOBALS['csstidy']['replace_colors']['turquoise'] = '#40e0d0'; $GLOBALS['csstidy']['replace_colors']['violet'] = '#ee82ee'; $GLOBALS['csstidy']['replace_colors']['violetred'] = '#d02090'; $GLOBALS['csstidy']['replace_colors']['wheat'] = '#f5deb3'; $GLOBALS['csstidy']['replace_colors']['whitesmoke'] = '#f5f5f5'; $GLOBALS['csstidy']['replace_colors']['yellowgreen'] = '#9acd32';   $GLOBALS['csstidy']['shorthands'] = array(); $GLOBALS['csstidy']['shorthands']['border-color'] = array('border-top-color','border-right-color','border-bottom-color','border-left-color'); $GLOBALS['csstidy']['shorthands']['border-style'] = array('border-top-style','border-right-style','border-bottom-style','border-left-style'); $GLOBALS['csstidy']['shorthands']['border-width'] = array('border-top-width','border-right-width','border-bottom-width','border-left-width'); $GLOBALS['csstidy']['shorthands']['margin'] = array('margin-top','margin-right','margin-bottom','margin-left'); $GLOBALS['csstidy']['shorthands']['padding'] = array('padding-top','padding-right','padding-bottom','padding-left'); $GLOBALS['csstidy']['shorthands']['-moz-border-radius'] = 0;   $GLOBALS['csstidy']['all_properties'] = array(); $GLOBALS['csstidy']['all_properties']['background'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['background-color'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['background-image'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['background-repeat'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['background-attachment'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['background-position'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-top'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-right'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-bottom'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-left'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-color'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-top-color'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-bottom-color'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-left-color'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-right-color'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-style'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-top-style'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-right-style'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-left-style'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-bottom-style'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-top-width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-right-width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-left-width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-bottom-width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-collapse'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['border-spacing'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['bottom'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['caption-side'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['content'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['clear'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['clip'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['color'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['counter-reset'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['counter-increment'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['cursor'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['empty-cells'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['display'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['direction'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['float'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font-family'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font-style'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font-variant'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font-weight'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['font-stretch'] = 'CSS2.0'; $GLOBALS['csstidy']['all_properties']['font-size-adjust'] = 'CSS2.0'; $GLOBALS['csstidy']['all_properties']['font-size'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['height'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['left'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['line-height'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['list-style'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['list-style-type'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['list-style-image'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['list-style-position'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['margin'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['margin-top'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['margin-right'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['margin-bottom'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['margin-left'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['marks'] = 'CSS1.0,CSS2.0'; $GLOBALS['csstidy']['all_properties']['marker-offset'] = 'CSS2.0'; $GLOBALS['csstidy']['all_properties']['max-height'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['max-width'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['min-height'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['min-width'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['overflow'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['orphans'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['outline'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['outline-width'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['outline-style'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['outline-color'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['padding'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['padding-top'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['padding-right'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['padding-bottom'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['padding-left'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['page-break-before'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['page-break-after'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['page-break-inside'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['page'] = 'CSS2.0'; $GLOBALS['csstidy']['all_properties']['position'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['quotes'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['right'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['size'] = 'CSS1.0,CSS2.0'; $GLOBALS['csstidy']['all_properties']['speak-header'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['table-layout'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['top'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['text-indent'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['text-align'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['text-decoration'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['text-shadow'] = 'CSS2.0'; $GLOBALS['csstidy']['all_properties']['letter-spacing'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['word-spacing'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['text-transform'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['white-space'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['unicode-bidi'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['vertical-align'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['visibility'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['width'] = 'CSS1.0,CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['widows'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['z-index'] = 'CSS1.0,CSS2.0,CSS2.1';  $GLOBALS['csstidy']['all_properties']['volume'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['speak'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['pause'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['pause-before'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['pause-after'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['cue'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['cue-before'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['cue-after'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['play-during'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['azimuth'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['elevation'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['speech-rate'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['voice-family'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['pitch'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['pitch-range'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['stress'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['richness'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['speak-punctuation'] = 'CSS2.0,CSS2.1'; $GLOBALS['csstidy']['all_properties']['speak-numeral'] = 'CSS2.0,CSS2.1';   $GLOBALS['csstidy']['predefined_templates']['default'][] = '<span class="at">';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '</span> <span class="format">{</span>'."\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = '<span class="selector">';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '</span> <span class="format">{</span>'."\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = '<span class="property">';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '</span><span class="value">';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '</span><span class="format">;</span>'."\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = '<span class="format">}</span>';  $GLOBALS['csstidy']['predefined_templates']['default'][] = "\n\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = "\n".'<span class="format">}</span>'. "\n\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = '';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '<span class="comment">';  $GLOBALS['csstidy']['predefined_templates']['default'][] = '</span>'."\n";  $GLOBALS['csstidy']['predefined_templates']['default'][] = "\n";   $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '<span class="at">'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '</span> <span class="format">{</span>'."\n"; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '<span class="selector">'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '</span><span class="format">{</span>'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '<span class="property">'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '</span><span class="value">'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '</span><span class="format">;</span>'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '<span class="format">}</span>'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = "\n"; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = "\n". '<span class="format">}'."\n".'</span>'; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = ''; $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '<span class="comment">';  $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = '</span>';  $GLOBALS['csstidy']['predefined_templates']['high_compression'][] = "\n";  $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="at">'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '</span><span class="format">{</span>'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="selector">'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '</span><span class="format">{</span>'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="property">'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '</span><span class="value">'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '</span><span class="format">;</span>'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="format">}</span>'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = ''; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="format">}</span>'; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = ''; $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '<span class="comment">';  $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '</span>';  $GLOBALS['csstidy']['predefined_templates']['highest_compression'][] = '';  $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '<span class="at">'; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '</span> <span class="format">{</span>'."\n"; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '<span class="selector">'; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '</span>'."\n".'<span class="format">{</span>'."\n"; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = ' <span class="property">'; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '</span><span class="value">'; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '</span><span class="format">;</span>'."\n"; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '<span class="format">}</span>'; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = "\n\n"; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = "\n".'<span class="format">}</span>'."\n\n"; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = ' '; $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '<span class="comment">';  $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = '</span>'."\n";  $GLOBALS['csstidy']['predefined_templates']['low_compression'][] = "\n";  

/**
 * Contains a class for printing CSS code
 *
 * @version 1.0
 */
     class csstidy_print {    var $input_css = '';     var $output_css = '';     var $output_css_plain = '';     function csstidy_print(&$css)  {  $this->parser =& $css;  $this->css =& $css->css;  $this->template =& $css->template;  $this->tokens =& $css->tokens;  $this->charset =& $css->charset;  $this->import =& $css->import;  $this->namespace =& $css->namespace;  }     function _reset()  {  $this->output_css = '';  $this->output_css_plain = '';  }     function plain()  {  $this->_print(true);  return $this->output_css_plain;  }     function formatted()  {  $this->_print(false);  return $this->output_css;  }     function formatted_page($doctype='xhtml1.1', $externalcss=true, $title='', $lang='en')  {  switch ($doctype) {  case 'xhtml1.0strict':  $doctype_output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';  break;  case 'xhtml1.1':  default:  $doctype_output = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"  "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';  break;  }    $output = $cssparsed = '';  $this->output_css_plain =& $output;    $output .= $doctype_output."\n".'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$lang.'"';  $output .= ($doctype === 'xhtml1.1') ? '>' : ' lang="'.$lang.'">';  $output .= "\n<head>\n <title>$title</title>";   if ($externalcss) {  $output .= "\n <style type=\"text/css\">\n";  $cssparsed = file_get_contents('cssparsed.css');  $output .= $cssparsed;   $output .= "\n</style>";  }  else {  $output .= "\n".' <link rel="stylesheet" type="text/css" href="cssparsed.css" />';  }  $output .= "\n</head>\n<body><code id=\"copytext\">";  $output .= $this->formatted();  $output .= '</code>'."\n".'</body></html>';  return $this->output_css_plain;  }     function _print($plain = false)  {  if ($this->output_css && $this->output_css_plain) {  return;  }   $output = '';  if (!$this->parser->get_cfg('preserve_css')) {  $this->_convert_raw_css();  }   $template =& $this->template;   if ($plain) {  $template = array_map('strip_tags', $template);  }   if ($this->parser->get_cfg('timestamp')) {  array_unshift($this->tokens, array(COMMENT, ' CSSTidy ' . $this->parser->version . ': ' . date('r') . ' '));  }   if (!empty($this->charset)) {  $output .= $template[0].'@charset '.$template[5].$this->charset.$template[6];  }   if (!empty($this->import)) {  for ($i = 0, $size = count($this->import); $i < $size; $i ++) {  if(substr($this->import[$i], 0, 4) === 'url(' && substr($this->import[$i], -1, 1) === ')') {  $this->import[$i] = '\''.substr($this->import[$i], 4, -1).'\'';  $this->parser->log('Optimised @import : Removed "url("','Information');  }  $output .= $template[0].'@import '.$template[5].$this->import[$i].$template[6];  }  }   if (!empty($this->namespace)) {  if(substr($this->namespace, 0, 4) === 'url(' && substr($this->namespace, -1, 1) === ')') {  $this->namespace = '\''.substr($this->namespace, 4, -1).'\'';  $this->parser->log('Optimised @namespace : Removed "url("','Information');  }  $output .= $template[0].'@namespace '.$template[5].$this->namespace.$template[6];  }   $output .= $template[13];  $in_at_out = '';  $out =& $output;   foreach ($this->tokens as $key => $token)  {  switch ($token[0])  {  case AT_START:  $out .= $template[0].$this->_htmlsp($token[1], $plain).$template[1];  $out =& $in_at_out;  break;   case SEL_START:  if($this->parser->get_cfg('lowercase_s')) $token[1] = strtolower($token[1]);    if (substr($token[1], 0, 10) === '@font-face') {  $token[1] = '@font-face';  }  $out .= ($token[1]{0} !== '@') ? $template[2].$this->_htmlsp($token[1], $plain) : $template[0].$this->_htmlsp($token[1], $plain);  $out .= $template[3];  break;   case PROPERTY:  if($this->parser->get_cfg('case_properties') === 2)  {  $token[1] = strtoupper($token[1]);    if (substr($token[1], 0, 6) == 'CURSOR') {  $token[1] = 'CURSOR';  }  if (substr($token[1], 0, 3) == 'SRC') {  $token[1] = 'SRC';  }  }  elseif($this->parser->get_cfg('case_properties') === 1)  {  $token[1] = strtolower($token[1]);    if (substr($token[1], 0, 6) == 'cursor') {  $token[1] = 'cursor';  }  if (substr($token[1], 0, 3) == 'src') {  $token[1] = 'src';  }  } else {    if (preg_match("/cursor|src/i", substr($token[1], 0, 6))) {  $token[1] = preg_replace("/(cursor|src)_[0-9]+/i", "$1", $token[1]);  }  }  $out .= $template[4] . $this->_htmlsp($token[1], $plain) . ':' . $template[5];  break;   case VALUE:  $out .= $this->_htmlsp($token[1], $plain);  if($this->_seeknocomment($key, 1) == SEL_END && $this->parser->get_cfg('remove_last_;')) {  $out .= str_replace(';', '', $template[6]);  } else {  $out .= $template[6];  }  break;   case SEL_END:  $out .= $template[7];  if($this->_seeknocomment($key, 1) != AT_END) $out .= $template[8];  break;   case AT_END:  $out =& $output;  $out .= $template[10] . str_replace("\n", "\n" . $template[10], $in_at_out);  $in_at_out = '';  $out .= $template[9];  break;   case COMMENT:  $out .= $template[11] . '' . $template[12];  break;  }  }   $output = trim($output);   if (!$plain) {  $this->output_css = $output;  $this->_print(true);  } else {    $this->output_css_plain = str_replace('&#160;', '', $output);  }  }     function _seeknocomment($key, $move) {  $go = ($move > 0) ? 1 : -1;  for ($i = $key + 1; abs($key-$i)-1 < abs($move); $i += $go) {  if (!isset($this->tokens[$i])) {  return;  }  if ($this->tokens[$i][0] == COMMENT) {  $move += 1;  continue;  }  return $this->tokens[$i][0];  }  }     function _convert_raw_css()  {  $this->tokens = array();   foreach ($this->css as $medium => $val)  {  if ($this->parser->get_cfg('sort_selectors')%2 == 1) ksort($val);  if ($medium != DEFAULT_AT) {  $this->parser->_add_token(AT_START, $medium, true);  }   foreach ($val as $selector => $vali)  {  if ($this->parser->get_cfg('sort_properties')) ksort($vali);   if ($this->parser->get_cfg('sort_selectors')%3 == 2) {  $selector = explode(",", $selector);  sort($selector);  $selector = implode(",", $selector);  }   $this->parser->_add_token(SEL_START, $selector, true);   foreach ($vali as $property => $valj)  {  $this->parser->_add_token(PROPERTY, $property, true);  $this->parser->_add_token(VALUE, $valj, true);  }   $this->parser->_add_token(SEL_END, $selector, true);  }   if ($medium != DEFAULT_AT) {  $this->parser->_add_token(AT_END, $medium, true);  }  }  }     function _htmlsp($string, $plain)  {  if (!$plain) {  return htmlspecialchars($string, ENT_QUOTES, 'utf-8');  }  return $string;  }     function get_ratio()  {  if (!$this->output_css_plain) {  $this->formatted();  }  return round((strlen($this->input_css) - strlen($this->output_css_plain)) / strlen($this->input_css), 3) * 100;  }     function get_diff()  {  if (!$this->output_css_plain) {  $this->formatted();  }   $diff = strlen($this->output_css_plain) - strlen($this->input_css);   if ($diff > 0) {  return '+' . $diff;  } elseif ($diff == 0) {  return '+-' . $diff;  }   return $diff;  }     function size($loc = 'output')  {  if ($loc === 'output' && !$this->output_css) {  $this->formatted();  }   if ($loc === 'input') {  return (strlen($this->input_css) / 1000);  } else {  return (strlen($this->output_css_plain) / 1000);  }  } } 

/**
 * Contains a class for optimising CSS code
 *
 * @version 1.0
 */
     class csstidy_optimise {    function csstidy_optimise(&$css)  {  $this->parser =& $css;  $this->css =& $css->css;  $this->sub_value =& $css->sub_value;  $this->at =& $css->at;  $this->selector =& $css->selector;  $this->property =& $css->property;  $this->value =& $css->value;  }     function postparse()  {  if ($this->parser->get_cfg('preserve_css')) {  return;  }   if ($this->parser->get_cfg('merge_selectors') === 2)  {  foreach ($this->css as $medium => $value)  {  $this->merge_selectors($this->css[$medium]);  }  }    if ($this->parser->get_cfg('discard_invalid_selectors')) {  foreach ($this->css as $medium => $value)  {  $this->discard_invalid_selectors($this->css[$medium]);  }  }   if ($this->parser->get_cfg('optimise_shorthands') > 0)  {  foreach ($this->css as $medium => $value)  {  foreach ($value as $selector => $value1)  {  $this->css[$medium][$selector] = csstidy_optimise::merge_4value_shorthands($this->css[$medium][$selector]);   if ($this->parser->get_cfg('optimise_shorthands') < 2) {  continue;  }   $this->css[$medium][$selector] = csstidy_optimise::merge_font($this->css[$medium][$selector]);   if ($this->parser->get_cfg('optimise_shorthands') < 3) {  continue;  }    $this->css[$medium][$selector] = csstidy_optimise::merge_bg($this->css[$medium][$selector]);  if (empty($this->css[$medium][$selector])) {  unset($this->css[$medium][$selector]);  }   }  }  }  }     function value()  {  $shorthands =& $GLOBALS['csstidy']['shorthands'];     if(isset($shorthands[$this->property]))  {  $temp = csstidy_optimise::shorthand($this->value);   if($temp != $this->value)  {  $this->parser->log('Optimised shorthand notation ('.$this->property.'): Changed "'.$this->value.'" to "'.$temp.'"','Information');  }  $this->value = $temp;  }     if($this->value != $this->compress_important($this->value))  {  $this->parser->log('Optimised !important','Information');  }  }     function shorthands()  {  $shorthands =& $GLOBALS['csstidy']['shorthands'];   if(!$this->parser->get_cfg('optimise_shorthands') || $this->parser->get_cfg('preserve_css')) {  return;  }   if($this->property === 'font' && $this->parser->get_cfg('optimise_shorthands') > 0)  {  unset($this->css[$this->at][$this->selector]['font']);  $this->parser->merge_css_blocks($this->at,$this->selector,csstidy_optimise::dissolve_short_font($this->value));  }  if($this->property === 'background' && $this->parser->get_cfg('optimise_shorthands') > 1)  {  unset($this->css[$this->at][$this->selector]['background']);  $this->parser->merge_css_blocks($this->at,$this->selector,csstidy_optimise::dissolve_short_bg($this->value));  }  if(isset($shorthands[$this->property]))  {  $this->parser->merge_css_blocks($this->at,$this->selector,csstidy_optimise::dissolve_4value_shorthands($this->property,$this->value));  if(is_array($shorthands[$this->property]))  {  unset($this->css[$this->at][$this->selector][$this->property]);  }  }  }     function subvalue()  {  $replace_colors =& $GLOBALS['csstidy']['replace_colors'];   $this->sub_value = trim($this->sub_value);  if($this->sub_value == '')   {  return;  }   $important = '';  if(csstidy::is_important($this->sub_value))  {  $important = '!important';  }  $this->sub_value = csstidy::gvw_important($this->sub_value);     if($this->property === 'font-weight' && $this->parser->get_cfg('compress_font-weight'))  {  if($this->sub_value === 'bold')  {  $this->sub_value = '700';  $this->parser->log('Optimised font-weight: Changed "bold" to "700"','Information');  }  else if($this->sub_value === 'normal')  {  $this->sub_value = '400';  $this->parser->log('Optimised font-weight: Changed "normal" to "400"','Information');  }  }   $temp = $this->compress_numbers($this->sub_value);  if(strcasecmp($temp, $this->sub_value) !== 0)  {  if(strlen($temp) > strlen($this->sub_value)) {  $this->parser->log('Fixed invalid number: Changed "'.$this->sub_value.'" to "'.$temp.'"','Warning');  } else {  $this->parser->log('Optimised number: Changed "'.$this->sub_value.'" to "'.$temp.'"','Information');  }  $this->sub_value = $temp;  }  if($this->parser->get_cfg('compress_colors'))  {  $temp = $this->cut_color($this->sub_value);  if($temp !== $this->sub_value)  {  if(isset($replace_colors[$this->sub_value])) {  $this->parser->log('Fixed invalid color name: Changed "'.$this->sub_value.'" to "'.$temp.'"','Warning');  } else {  $this->parser->log('Optimised color: Changed "'.$this->sub_value.'" to "'.$temp.'"','Information');  }  $this->sub_value = $temp;  }  }  $this->sub_value .= $important;  }     function shorthand($value)  {  $important = '';  if(csstidy::is_important($value))  {  $values = csstidy::gvw_important($value);  $important = '!important';  }  else $values = $value;   $values = explode(' ',$values);  switch(count($values))  {  case 4:  if($values[0] == $values[1] && $values[0] == $values[2] && $values[0] == $values[3])  {  return $values[0].$important;  }  elseif($values[1] == $values[3] && $values[0] == $values[2])  {  return $values[0].' '.$values[1].$important;  }  elseif($values[1] == $values[3])  {  return $values[0].' '.$values[1].' '.$values[2].$important;  }  break;   case 3:  if($values[0] == $values[1] && $values[0] == $values[2])  {  return $values[0].$important;  }  elseif($values[0] == $values[2])  {  return $values[0].' '.$values[1].$important;  }  break;   case 2:  if($values[0] == $values[1])  {  return $values[0].$important;  }  break;  }   return $value;  }     function compress_important(&$string)  {  if(csstidy::is_important($string))  {  $string = csstidy::gvw_important($string) . '!important';  }  return $string;  }     function cut_color($color)  {  $replace_colors =& $GLOBALS['csstidy']['replace_colors'];     if(strtolower(substr($color,0,4)) === 'rgb(')  {  $color_tmp = substr($color,4,strlen($color)-5);  $color_tmp = explode(',',$color_tmp);  for ( $i = 0; $i < count($color_tmp); $i++ )  {  $color_tmp[$i] = trim ($color_tmp[$i]);  if(substr($color_tmp[$i],-1) === '%')  {  $color_tmp[$i] = round((255*$color_tmp[$i])/100);  }  if($color_tmp[$i]>255) $color_tmp[$i] = 255;  }  $color = '#';  for ($i = 0; $i < 3; $i++ )  {  if($color_tmp[$i]<16) {  $color .= '0' . dechex($color_tmp[$i]);  } else {  $color .= dechex($color_tmp[$i]);  }  }  }     if(isset($replace_colors[strtolower($color)]))  {  $color = $replace_colors[strtolower($color)];  }     if(strlen($color) == 7)  {  $color_temp = strtolower($color);  if($color_temp{0} === '#' && $color_temp{1} == $color_temp{2} && $color_temp{3} == $color_temp{4} && $color_temp{5} == $color_temp{6})  {  $color = '#'.$color{1}.$color{3}.$color{5};  }  }   switch(strtolower($color))  {    case 'black': return '#000';  case 'fuchsia': return '#f0f';  case 'white': return '#fff';  case 'yellow': return '#ff0';     case '#800000': return 'maroon';  case '#ffa500': return 'orange';  case '#808000': return 'olive';  case '#800080': return 'purple';  case '#008000': return 'green';  case '#000080': return 'navy';  case '#008080': return 'teal';  case '#c0c0c0': return 'silver';  case '#808080': return 'gray';  case '#f00': return 'red';  }   return $color;  }     function compress_numbers($subvalue)  {  $unit_values =& $GLOBALS['csstidy']['unit_values'];  $color_values =& $GLOBALS['csstidy']['color_values'];     if($this->property === 'font')  {  $temp = explode('/',$subvalue);  }  else  {  $temp = array($subvalue);  }  for ($l = 0; $l < count($temp); $l++)  {    $number = $this->AnalyseCssNumber($temp[$l]);  if ($number === false)  {  return $subvalue;  }     if (in_array($this->property, $color_values))  {  $temp[$l] = '#'.$temp[$l];  continue;  }    if (abs($number[0]) > 0) {  if ($number[1] == '' && in_array($this->property,$unit_values,true))  {  $number[1] = 'px';  }  } else {  $number[1] = '';  }    $temp[$l] = $number[0] . $number[1];  }   return ((count($temp) > 1) ? $temp[0].'/'.$temp[1] : $temp[0]);  }      function AnalyseCssNumber($string)  {    if (strlen($string) == 0 || ctype_alpha($string{0})) {  return false;  }    $units =& $GLOBALS['csstidy']['units'];  $return = array(0, '');    $return[0] = floatval($string);  if (abs($return[0]) > 0 && abs($return[0]) < 1) {  if ($return[0] < 0) {  $return[0] = '-' . ltrim(substr($return[0], 1), '0');  } else {  $return[0] = ltrim($return[0], '0');  }  }      foreach ($units as $unit)  {  $expectUnitAt = strlen($string) - strlen($unit);  if( ! ($unitInString = stristr( $string, $unit )) )  {   continue;  }  $actualPosition = strpos($string, $unitInString);  if ($expectUnitAt === $actualPosition)  {  $return[1] = $unit;  $string = substr($string, 0, - strlen($unit));  break;  }  }  if (!is_numeric($string)) {  return false;  }  return $return;  }     function merge_selectors(&$array)  {  $css = $array;  foreach($css as $key => $value)  {  if(!isset($css[$key]))  {  continue;  }  $newsel = '';     $keys = array();    foreach($css as $selector => $vali)  {  if($selector == $key)  {  continue;  }   if($css[$key] === $vali)  {  $keys[] = $selector;  }  }   if(!empty($keys))  {  $newsel = $key;  unset($css[$key]);  foreach($keys as $selector)  {  unset($css[$selector]);  $newsel .= ','.$selector;  }  $css[$newsel] = $value;  }  }  $array = $css;  }      function discard_invalid_selectors(&$array) {  $invalid = array('+' => true, '~' => true, ',' => true, '>' => true);  foreach ($array as $selector => $decls) {  $ok = true;  $selectors = array_map('trim', explode(',', $selector));  foreach ($selectors as $s) {  $simple_selectors = preg_split('/\s*[+>~\s]\s*/', $s);  foreach ($simple_selectors as $ss) {  if ($ss === '') $ok = false;    }  }  if (!$ok) unset($array[$selector]);  }  }     function dissolve_4value_shorthands($property,$value)  {  $shorthands =& $GLOBALS['csstidy']['shorthands'];  if(!is_array($shorthands[$property]))  {  $return[$property] = $value;  return $return;  }   $important = '';  if(csstidy::is_important($value))  {  $value = csstidy::gvw_important($value);  $important = '!important';  }  $values = explode(' ',$value);    $return = array();  if(count($values) == 4)  {  for($i=0;$i<4;$i++)  {  $return[$shorthands[$property][$i]] = $values[$i].$important;  }  }  elseif(count($values) == 3)  {  $return[$shorthands[$property][0]] = $values[0].$important;  $return[$shorthands[$property][1]] = $values[1].$important;  $return[$shorthands[$property][3]] = $values[1].$important;  $return[$shorthands[$property][2]] = $values[2].$important;  }  elseif(count($values) == 2)  {  for($i=0;$i<4;$i++)  {  $return[$shorthands[$property][$i]] = (($i % 2 != 0)) ? $values[1].$important : $values[0].$important;  }  }  else  {  for($i=0;$i<4;$i++)  {  $return[$shorthands[$property][$i]] = $values[0].$important;  }  }   return $return;  }     function explode_ws($sep,$string)  {  $status = 'st';  $to = '';   $output = array();  $num = 0;  for($i = 0, $len = strlen($string);$i < $len; $i++)  {  switch($status)  {  case 'st':  if($string{$i} == $sep && !csstidy::escaped($string,$i))  {  ++$num;  }  elseif($string{$i} === '"' || $string{$i} === '\'' || $string{$i} === '(' && !csstidy::escaped($string,$i))  {  $status = 'str';  $to = ($string{$i} === '(') ? ')' : $string{$i};  (isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};  }  else  {  (isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};  }  break;   case 'str':  if($string{$i} == $to && !csstidy::escaped($string,$i))  {  $status = 'st';  }  (isset($output[$num])) ? $output[$num] .= $string{$i} : $output[$num] = $string{$i};  break;  }  }   if(isset($output[0]))  {  return $output;  }  else  {  return array($output);  }  }     function merge_4value_shorthands($array)  {  $return = $array;  $shorthands =& $GLOBALS['csstidy']['shorthands'];   foreach($shorthands as $key => $value)  {  if(isset($array[$value[0]]) && isset($array[$value[1]])  && isset($array[$value[2]]) && isset($array[$value[3]]) && $value !== 0)  {  $return[$key] = '';   $important = '';  for($i = 0; $i < 4; $i++)  {  $val = $array[$value[$i]];  if(csstidy::is_important($val))  {  $important = '!important';  $return[$key] .= csstidy::gvw_important($val).' ';  }  else  {  $return[$key] .= $val.' ';  }  unset($return[$value[$i]]);  }  $return[$key] = csstidy_optimise::shorthand(trim($return[$key].$important));  }  }  return $return;  }     function dissolve_short_bg($str_value)  {  $background_prop_default =& $GLOBALS['csstidy']['background_prop_default'];  $repeat = array('repeat','repeat-x','repeat-y','no-repeat','space');  $attachment = array('scroll','fixed','local');  $clip = array('border','padding');  $origin = array('border','padding','content');  $pos = array('top','center','bottom','left','right');  $important = '';  $return = array('background-image' => null,'background-size' => null,'background-repeat' => null,'background-position' => null,'background-attachment'=>null,'background-clip' => null,'background-origin' => null,'background-color' => null);   if(csstidy::is_important($str_value))  {  $important = ' !important';  $str_value = csstidy::gvw_important($str_value);  }   $str_value = csstidy_optimise::explode_ws(',',$str_value);  for($i = 0; $i < count($str_value); $i++)  {  $have['clip'] = false; $have['pos'] = false;  $have['color'] = false; $have['bg'] = false;    if (is_array($str_value[$i])) {  $str_value[$i] = $str_value[$i][0];  }  $str_value[$i] = csstidy_optimise::explode_ws(' ',trim($str_value[$i]));   for($j = 0; $j < count($str_value[$i]); $j++)  {  if($have['bg'] === false && (substr($str_value[$i][$j],0,4) === 'url(' || $str_value[$i][$j] === 'none'))  {  $return['background-image'] .= $str_value[$i][$j].',';  $have['bg'] = true;  }  elseif(in_array($str_value[$i][$j],$repeat,true))  {  $return['background-repeat'] .= $str_value[$i][$j].',';  }  elseif(in_array($str_value[$i][$j],$attachment,true))  {  $return['background-attachment'] .= $str_value[$i][$j].',';  }  elseif(in_array($str_value[$i][$j],$clip,true) && !$have['clip'])  {  $return['background-clip'] .= $str_value[$i][$j].',';  $have['clip'] = true;  }  elseif(in_array($str_value[$i][$j],$origin,true))  {  $return['background-origin'] .= $str_value[$i][$j].',';  }  elseif($str_value[$i][$j]{0} === '(')  {  $return['background-size'] .= substr($str_value[$i][$j],1,-1).',';  }  elseif(in_array($str_value[$i][$j],$pos,true) || is_numeric($str_value[$i][$j]{0}) || $str_value[$i][$j]{0} === null || $str_value[$i][$j]{0} === '-' || $str_value[$i][$j]{0} === '.')  {  $return['background-position'] .= $str_value[$i][$j];  if(!$have['pos']) $return['background-position'] .= ' '; else $return['background-position'].= ',';  $have['pos'] = true;  }  elseif(!$have['color'])  {  $return['background-color'] .= $str_value[$i][$j].',';  $have['color'] = true;  }  }  }   foreach($background_prop_default as $bg_prop => $default_value)  {  if($return[$bg_prop] !== null)  {  $return[$bg_prop] = substr($return[$bg_prop],0,-1).$important;  }  else $return[$bg_prop] = $default_value.$important;  }  return $return;  }     function merge_bg($input_css)  {  $background_prop_default =& $GLOBALS['csstidy']['background_prop_default'];    $number_of_values = @max(count(csstidy_optimise::explode_ws(',',$input_css['background-image'])),count(csstidy_optimise::explode_ws(',',$input_css['background-color'])),1);    $bg_img_array = @csstidy_optimise::explode_ws(',',csstidy::gvw_important($input_css['background-image']));  $new_bg_value = '';  $important = '';   for($i = 0; $i < $number_of_values; $i++)  {  foreach($background_prop_default as $bg_property => $default_value)  {    if(!isset($input_css[$bg_property]))  {  continue;  }   $cur_value = $input_css[$bg_property];     if((!isset($bg_img_array[$i]) || $bg_img_array[$i] === 'none')  && ($bg_property === 'background-size' || $bg_property === 'background-position'  || $bg_property === 'background-attachment' || $bg_property === 'background-repeat'))  {  continue;  }     if(csstidy::is_important($cur_value))  {  $important = ' !important';  $cur_value = csstidy::gvw_important($cur_value);  }     if($cur_value === $default_value)  {  continue;  }   $temp = csstidy_optimise::explode_ws(',',$cur_value);   if(isset($temp[$i]))  {  if($bg_property === 'background-size')  {  $new_bg_value .= '('.$temp[$i].') ';  }  else  {  $new_bg_value .= $temp[$i].' ';  }  }  }   $new_bg_value = trim($new_bg_value);  if($i != $number_of_values-1) $new_bg_value .= ',';  }     foreach($background_prop_default as $bg_property => $default_value)  {  unset($input_css[$bg_property]);  }     if($new_bg_value !== '') $input_css['background'] = $new_bg_value.$important;   return $input_css;  }     function dissolve_short_font($str_value)  {  $font_prop_default =& $GLOBALS['csstidy']['font_prop_default'];  $font_weight = array('normal','bold','bolder','lighter',100,200,300,400,500,600,700,800,900);  $font_variant = array('normal','small-caps');  $font_style = array('normal','italic','oblique');  $important = '';  $return = array('font-style' => null,'font-variant'=>null,'font-weight' => null,'font-size' => null,'line-height' => null,'font-family' => null);   if(csstidy::is_important($str_value))  {  $important = '!important';  $str_value = csstidy::gvw_important($str_value);  }   $have['style'] = false; $have['variant'] = false;  $have['weight'] = false; $have['size'] = false;    $multiwords = false;     $str_value = csstidy_optimise::explode_ws(',',trim($str_value));    $str_value[0] = csstidy_optimise::explode_ws(' ',trim($str_value[0]));   for($j = 0; $j < count($str_value[0]); $j++)  {  if($have['weight'] === false && in_array($str_value[0][$j], $font_weight))  {  $return['font-weight'] = $str_value[0][$j];  $have['weight'] = true;  }  elseif($have['variant'] === false && in_array($str_value[0][$j], $font_variant))  {  $return['font-variant'] = $str_value[0][$j];  $have['variant'] = true;  }  elseif($have['style'] === false && in_array($str_value[0][$j], $font_style))  {  $return['font-style'] = $str_value[0][$j];  $have['style'] = true;  }  elseif ($have['size'] === false && (is_numeric($str_value[0][$j]{0}) || $str_value[0][$j]{0} === null || $str_value[0][$j]{0} === '.'))  {  $size = csstidy_optimise::explode_ws('/',trim($str_value[0][$j]));  $return['font-size'] = $size[0];  if (isset($size[1])) {  $return['line-height'] = $size[1];  }  $have['size'] = true;  }  else  {  if (isset($return['font-family'])) {  $return['font-family'] .= ' ' . $str_value[0][$j];  $multiwords = true;  } else {  $return['font-family'] = $str_value[0][$j];  }  }  }    if ($multiwords !== false) {  $return['font-family'] = '"' . $return['font-family'] . '"';  }  $i = 1;  while (isset($str_value[$i])) {  $return['font-family'] .= ','.trim($str_value[$i]);  $i++;  }      if ($have['size'] === false && isset($return['font-weight']) &&  is_numeric($return['font-weight']{0}))  {  $return['font-size'] = $return['font-weight'];  unset($return['font-weight']);  }   foreach($font_prop_default as $font_prop => $default_value)  {  if($return[$font_prop] !== null)  {  $return[$font_prop] = $return[$font_prop].$important;  }  else $return[$font_prop] = $default_value.$important;  }  return $return;  }     function merge_font($input_css)  {  $font_prop_default =& $GLOBALS['csstidy']['font_prop_default'];  $new_font_value = '';  $important = '';    if (isset($input_css['font-family']) && isset($input_css['font-size']))  {    if (isset($input_css['font-family']))  {  $families = explode(",", $input_css['font-family']);  $result_families = array();  foreach ($families as $family)  {  $family = trim($family);  $len = strlen($family);  if (strpos($family, " ") &&  !(($family{0} == '"' && $family{$len - 1} == '"') ||  ($family{0} == "'" && $family{$len - 1} == "'")))  {  $family = '"' . $family . '"';  }  $result_families[] = $family;  }  $input_css['font-family'] = implode(",", $result_families);  }  foreach($font_prop_default as $font_property => $default_value)  {     if(!isset($input_css[$font_property]))  {  continue;  }    $cur_value = $input_css[$font_property];      if ($cur_value === $default_value)  {  continue;  }     if(csstidy::is_important($cur_value))  {  $important = '!important';  $cur_value = csstidy::gvw_important($cur_value);  }   $new_font_value .= $cur_value;    $new_font_value .= ($font_property === 'font-size' &&  isset($input_css['line-height'])) ? '/' : ' ';  }   $new_font_value = trim($new_font_value);     foreach($font_prop_default as $font_property => $default_value)  {  unset($input_css[$font_property]);  }     if($new_font_value !== '')  {  $input_css['font'] = $new_font_value . $important;  }  }   return $input_css;  } }  

/**
 * CSS Parser class
 *
 
 * This class represents a CSS parser which reads CSS code and saves it in an array.
 * In opposite to most other CSS parsers, it does not use regular expressions and
 * thus has full CSS2 support and a higher reliability.
 * Additional to that it applies some optimisations and fixes to the CSS code.
 * An online version should be available here: http://cdburnerxp.se/cssparse/css_optimiser.php
 * @package csstidy
 * @author Florian Schmitz (floele at gmail dot com) 2005-2006
 * @version 1.3
 */
class csstidy {

/**
 * Saves the parsed CSS. This array is empty if preserve_css is on.
 * @var array
 * @access public
 */
var $css = array();

/**
 * Saves the parsed CSS (raw)
 * @var array
 * @access private
 */
var $tokens = array();

/**
 * Printer class
 * @see csstidy_print
 * @var object
 * @access public
 */
var $print;

/**
 * Optimiser class
 * @see csstidy_optimise
 * @var object
 * @access private
 */
var $optimise;

/**
 * Saves the CSS charset (@charset)
 * @var string
 * @access private
 */
var $charset = '';

/**
 * Saves all @import URLs
 * @var array
 * @access private
 */
var $import = array();

/**
 * Saves the namespace
 * @var string
 * @access private
 */
var $namespace = '';

/**
 * Contains the version of csstidy
 * @var string
 * @access private
 */
var $version = '1.3';

/**
 * Stores the settings
 * @var array
 * @access private
 */
var $settings = array();

/**
 * Saves the parser-status.
 *
 * Possible values:
 * - is = in selector
 * - ip = in property
 * - iv = in value
 * - instr = in string (started at " or ' or ( )
 * - ic = in comment (ignore everything)
 * - at = in @-block
 *
 * @var string
 * @access private
 */
var $status = 'is';


/**
 * Saves the current at rule (@media)
 * @var string
 * @access private
 */
var $at = '';

/**
 * Saves the current selector
 * @var string
 * @access private
 */
var $selector = '';

/**
 * Saves the current property
 * @var string
 * @access private
 */
var $property = '';

/**
 * Saves the position of , in selectors
 * @var array
 * @access private
 */
var $sel_separate = array();

/**
 * Saves the current value
 * @var string
 * @access private
 */
var $value = '';

/**
 * Saves the current sub-value
 *
 * Example for a subvalue:
 * background:url(foo.png) red no-repeat;
 * "url(foo.png)", "red", and  "no-repeat" are subvalues,
 * seperated by whitespace
 * @var string
 * @access private
 */
var $sub_value = '';

/**
 * Array which saves all subvalues for a property.
 * @var array
 * @see sub_value
 * @access private
 */
var $sub_value_arr = array();

/**
 * Saves the char which opened the last string
 * @var string
 * @access private
 */
var $str_char = '';
var $cur_string = '';

/**
 * Status from which the parser switched to ic or instr
 * @var string
 * @access private
 */
var $from = '';

/**
 * Variable needed to manage string-in-strings, for example url("foo.png")
 * @var string
 * @access private
 */
var $str_in_str = false;

/**
 * =true if in invalid at-rule
 * @var bool
 * @access private
 */
var $invalid_at = false;

/**
 * =true if something has been added to the current selector
 * @var bool
 * @access private
 */
var $added = false;

/**
 * Array which saves the message log
 * @var array
 * @access private
 */
var $log = array();

/**
 * Saves the line number
 * @var integer
 * @access private
 */
var $line = 1;

/**
 * Marks if we need to leave quotes for a string
 * @var string
 * @access private
 */
var $quoted_string = false;

/**
 * Number of @font-face constructions
 * @var number
 * @access private
 */
var $fontface = 0;

/**
 * Loads standard template and sets default settings
 * @access private
 * @version 1.3
 */
function csstidy()
{
	$this->settings['remove_bslash'] = true;
	$this->settings['compress_colors'] = true;
	$this->settings['compress_font-weight'] = true;
	$this->settings['lowercase_s'] = false;
/*
1 common shorthands optimization
2 + font property optimization
3 + background property optimization
*/
	$this->settings['optimise_shorthands'] = 1;
	$this->settings['remove_last_;'] = true;
/* rewrite all properties with low case, better for later gzip */
	$this->settings['case_properties'] = 1;
/* sort properties in alpabetic order, better for later gzip */
	$this->settings['sort_properties'] = true;
/*
1, 3, 5, etc -- enable sorting selectors inside @media: a{}b{}c{}
2, 5, 8, etc -- enable sorting selectors inside one CSS declaration: a,b,c{}
*/
	$this->settings['sort_selectors'] = 2;
/* is dangeroues to be used: CSS is broken sometimes */
	$this->settings['merge_selectors'] = 0;
/* preserve or not browser hacks */
    $this->settings['discard_invalid_selectors'] = false;
	$this->settings['discard_invalid_properties'] = false;
	$this->settings['css_level'] = 'CSS2.1';
    $this->settings['preserve_css'] = false;
    $this->settings['timestamp'] = false;
    $this->optimise = new csstidy_optimise($this);
}

/**
 * Get the value of a setting.
 * @param string $setting
 * @access public
 * @return mixed
 * @version 1.0
 */
function get_cfg($setting)
{
	if(isset($this->settings[$setting]))
	{
		return $this->settings[$setting];
	}
	return false;
}

/**
 * Load a template
 * @param string $template used by set_cfg to load a template via a configuration setting
 * @access private
 * @version 1.4
 */
function _load_template($template)
{
	switch($template)
		{
			case 'default':
			$this->load_template('default');
			break;

			case 'highest':
			$this->load_template('highest_compression');
			break;

			case 'high':
			$this->load_template('high_compression');
			break;

			case 'low':
			$this->load_template('low_compression');
			break;

			default:
			$this->load_template($template);
			break;

		}
}

/**
 * Set the value of a setting.
 * @param string $setting
 * @param mixed $value
 * @access public
 * @return bool
 * @version 1.0
 */
function set_cfg($setting,$value=null)
{
	if (is_array($setting) && $value === null) {
		foreach($setting as $setprop => $setval) {
			$this->settings[$setprop] = $setval;
		}
		if (array_key_exists('template', $setting)) {
			$this->_load_template($this->settings['template']);
		}
		return true;
	}
	else if(isset($this->settings[$setting]) && $value !== '')
	{
		$this->settings[$setting] = $value;
		if ($setting === 'template') {
			$this->_load_template($this->settings['template']);
		}
		return true;
	}
	return false;
}

/**
 * Adds a token to $this->tokens
 * @param mixed $type
 * @param string $data
 * @param bool $do add a token even if preserve_css is off
 * @access private
 * @version 1.0
 */
function _add_token($type, $data, $do = false) {
    if($this->get_cfg('preserve_css') || $do) {
        $this->tokens[] = array($type, ($type == COMMENT) ? $data : trim($data));
    }
}

/**
 * Add a message to the message log
 * @param string $message
 * @param string $type
 * @param integer $line
 * @access private
 * @version 1.0
 */
function log($message,$type,$line = -1)
{
	if($line === -1)
	{
		$line = $this->line;
	}
	$line = intval($line);
	$add = array('m' => $message, 't' => $type);
	if(!isset($this->log[$line]) || !in_array($add,$this->log[$line]))
	{
		$this->log[$line][] = $add;
	}
}

/**
 * Parse unicode notations and find a replacement character
 * @param string $string
 * @param integer $i
 * @access private
 * @return string
 * @version 1.2
 */
function _unicode(&$string, &$i)
{
	++$i;
	$add = '';
	$tokens =& $GLOBALS['csstidy']['tokens'];
	$replaced = false;

	while($i < strlen($string) && (ctype_xdigit($string{$i}) || ctype_space($string{$i})) && strlen($add) < 6)
	{
		$add .= $string{$i};

		if(ctype_space($string{$i})) {
			break;
		}
		$i++;
	}

	if(hexdec($add) > 47 && hexdec($add) < 58 || hexdec($add) > 64 && hexdec($add) < 91 || hexdec($add) > 96 && hexdec($add) < 123)
	{
		$this->log('Replaced unicode notation: Changed \\'. $add .' to ' . chr(hexdec($add)),'Information');
		$add = chr(hexdec($add));
		$replaced = true;
	}
	else {
		$add = trim('\\'.$add);
	}

	if(@ctype_xdigit($string{$i+1}) && ctype_space($string{$i})
       && !$replaced || !ctype_space($string{$i})) {
		$i--;
	}

	if($add !== '\\' || !$this->get_cfg('remove_bslash') || strpos($tokens, $string{$i+1}) !== false) {
		return $add;
	}

	if($add === '\\') {
		$this->log('Removed unnecessary backslash','Information');
	}
	return '';
}

/**
 * Write formatted output to a file
 * @param string $filename
  * @param string $doctype when printing formatted, is a shorthand for the document type
 * @param bool $externalcss when printing formatted, indicates whether styles to be attached internally or as an external stylesheet
 * @param string $title when printing formatted, is the title to be added in the head of the document
 * @param string $lang when printing formatted, gives a two-letter language code to be added to the output
 * @access public
  * @version 1.4
 */
function write_page($filename, $doctype='xhtml1.1', $externalcss=true, $title='', $lang='en')
{
	$this->write($filename, true);
}

/**
 * Write plain output to a file
 * @param string $filename
 * @param bool $formatted whether to print formatted or not
 * @param string $doctype when printing formatted, is a shorthand for the document type
 * @param bool $externalcss when printing formatted, indicates whether styles to be attached internally or as an external stylesheet
 * @param string $title when printing formatted, is the title to be added in the head of the document
 * @param string $lang when printing formatted, gives a two-letter language code to be added to the output
 * @param bool $pre_code whether to add pre and code tags around the code (for light HTML formatted templates)
 * @access public
  * @version 1.4
 */
function write($filename, $formatted=false, $doctype='xhtml1.1', $externalcss=true, $title='', $lang='en', $pre_code=true)
{
	$filename .= ($formatted) ? '.xhtml' : '.css';
	
	if (!is_dir('temp')) {
		$madedir = mkdir('temp');
		if (!$madedir) {
			print 'Could not make directory "temp" in '.dirname(__FILE__);
			exit;
		}
	}
	$handle = fopen('temp/'.$filename, 'w');
	if($handle) {
		if (!$formatted) {
			fwrite($handle, $this->print->plain());
		}
		else {
			fwrite($handle, $this->print->formatted_page($doctype, $externalcss, $title, $lang, $pre_code));
		}
	}
	fclose($handle);
}

/**
 * Loads a new template
 * @param string $content either filename (if $from_file == true), content of a template file, "high_compression", "highest_compression", "low_compression", or "default"
 * @param bool $from_file uses $content as filename if true
 * @access public
 * @version 1.1
 * @see http://csstidy.sourceforge.net/templates.php
 */
function load_template($content, $from_file=true)
{
	$predefined_templates =& $GLOBALS['csstidy']['predefined_templates'];
	if($content === 'high_compression' || $content === 'default' || $content === 'highest_compression' || $content === 'low_compression')
	{
		$this->template = $predefined_templates[$content];
		return;
	}


	if($from_file)
	{
		$content = strip_tags(file_get_contents($content),'<span>');
	}
	$content = str_replace("\r\n","\n",$content); // Unify newlines (because the output also only uses \n)
	$template = explode('|',$content);

	for ($i = 0; $i < count($template); $i++ )
	{
		$this->template[$i] = $template[$i];
	}
}

/**
 * Starts parsing from URL
 * @param string $url
 * @access public
 * @version 1.0
 */
function parse_from_url($url)
{
	return $this->parse(@file_get_contents($url));
}

/**
 * Checks if there is a token at the current position
 * @param string $string
 * @param integer $i
 * @access public
 * @version 1.11
 */
function is_token(&$string, $i)
{
	$tokens =& $GLOBALS['csstidy']['tokens'];
	return (strpos($tokens, $string{$i}) !== false && !csstidy::escaped($string,$i));
}


/**
 * Parses CSS in $string. The code is saved as array in $this->css
 * @param string $string the CSS code
 * @access public
 * @return bool
 * @version 1.1
 */
function parse($string) {
    // Temporarily set locale to en_US in order to handle floats properly
    $old = @setlocale(LC_ALL, 0);
    @setlocale(LC_ALL, 'C');

    // PHP bug? Settings need to be refreshed in PHP4
    $this->print = new csstidy_print($this);
    $this->optimise = new csstidy_optimise($this);

    $all_properties =& $GLOBALS['csstidy']['all_properties'];
    $at_rules =& $GLOBALS['csstidy']['at_rules'];

    $this->css = array();
    $this->print->input_css = $string;
    $string = str_replace("\r\n","\n",$string) . ' ';
    $cur_comment = '';

    for ($i = 0, $size = strlen($string); $i < $size; $i++ )
    {
        if($string{$i} === "\n" || $string{$i} === "\r")
        {
            ++$this->line;
        }

        switch($this->status)
        {
            /* Case in at-block */
            case 'at':
            if(csstidy::is_token($string,$i))
            {
                if($string{$i} === '/' && @$string{$i+1} === '*')
                {
                    $this->status = 'ic'; ++$i;
                    $this->from = 'at';
                }
                elseif($string{$i} === '{')
                {
                    $this->status = 'is';
                    $this->_add_token(AT_START, $this->at);
                }
                elseif($string{$i} === ',')
                {
                    $this->at = trim($this->at).',';
                }
                elseif($string{$i} === '\\')
                {
                    $this->at .= $this->_unicode($string,$i);
                }
                // fix for complicated media, i.e @media screen and (-webkit-min-device-pixel-ratio:0)
                elseif(in_array($string{$i}, array('(', ')', ':')))
                {
                    $this->at .= $string{$i};
                }
            }
            else
            {
                $lastpos = strlen($this->at)-1;
                if(!( (ctype_space($this->at{$lastpos}) || csstidy::is_token($this->at,$lastpos) && $this->at{$lastpos} === ',') && ctype_space($string{$i})))
                {
                    $this->at .= $string{$i};
                }
            }
            break;

            /* Case in-selector */
            case 'is':
            if(csstidy::is_token($string,$i))
            {
                if($string{$i} === '/' && @$string{$i+1} === '*' && trim($this->selector) == '') {
                    $this->status = 'ic'; ++$i;
                    $this->from = 'is';
                } elseif($string{$i} === '@' && trim($this->selector) == '') {
                    // Check for at-rule
                    $this->invalid_at = true;
                    foreach($at_rules as $name => $type)
                    {
                        if(!strcasecmp(substr($string,$i+1,strlen($name)),$name))
                        {
                            ($type === 'at') ? $this->at = '@'.$name : $this->selector = '@'.$name;
                            $this->status = $type;
                            $i += strlen($name);
                            $this->invalid_at = false;
                        }
                    }
					// add fake counter not to rewrite @font-face
					switch ($this->selector) {
						case '@font-face':
							$this->selector .= '_' . (++$this->fontface);
							break;
					}

                    if($this->invalid_at)
                    {
                        $this->selector = '@';
                        $invalid_at_name = '';
                        for($j = $i+1; $j < $size; ++$j)
                        {
                            if(!ctype_alpha($string{$j}))
                            {
                                break;
                            }
                            $invalid_at_name .= $string{$j};
                        }
                        $this->log('Invalid @-rule: '.$invalid_at_name.' (removed)','Warning');
                    }
                } elseif(($string{$i} === '"' || $string{$i} === "'")) {
                    $this->cur_string = $string{$i};
                    $this->status = 'instr';
                    $this->str_char = $string{$i};
                    $this->from = 'is';
					/* fixing CSS3 attribute selectors, i.e. a[href$=".mp3" */
					$this->quoted_string = ($string{$i-1} == '=');
                } elseif($this->invalid_at && $string{$i} === ';') {
                    $this->invalid_at = false;
                    $this->status = 'is';
                } elseif($string{$i} === '{') {
                    $this->status = 'ip';
                    $this->_add_token(SEL_START, $this->selector);
                    $this->added = false;
                } elseif($string{$i} === '}') {
                    $this->_add_token(AT_END, $this->at);
                    $this->at = '';
                    $this->selector = '';
                    $this->sel_separate = array();
                } elseif($string{$i} === ',') {
                    $this->selector = trim($this->selector).',';
                    $this->sel_separate[] = strlen($this->selector);
                } elseif($string{$i} === '\\') {
                    $this->selector .= $this->_unicode($string,$i);
                } elseif($string{$i} === '*' && @in_array($string{$i+1}, array('.', '#', '[', ':'))) {
                    // remove unnecessary universal selector, FS#147
                } else {
                    $this->selector .= $string{$i};
                }
                
            }
            else
            {
                $lastpos = strlen($this->selector)-1;
                if($lastpos == -1 || !( (ctype_space($this->selector{$lastpos}) || csstidy::is_token($this->selector,$lastpos) && $this->selector{$lastpos} === ',') && ctype_space($string{$i})))
                {
                    $this->selector .= $string{$i};
                }
            }
            break;

            /* Case in-property */
            case 'ip':
            if(csstidy::is_token($string,$i))
            {
                if(($string{$i} === ':' || $string{$i} === '=') && $this->property != '')
                {
                    $this->status = 'iv';
                    if(!$this->get_cfg('discard_invalid_properties') || csstidy::property_is_valid($this->property)) {
                        $this->_add_token(PROPERTY, $this->property);
						/* fix MS expressions */
						if (in_array(strtolower($this->property), array('-ms-filter', 'filter', 'zoom'))) {
							$this->quoted_string = true;
						}
                    }
                }
                elseif($string{$i} === '/' && @$string{$i+1} === '*' && $this->property == '')
                {
                    $this->status = 'ic'; ++$i;
                    $this->from = 'ip';
                }
                elseif($string{$i} === '}')
                {
                    $this->explode_selectors();
                    $this->status = 'is';
                    $this->invalid_at = false;
                    $this->_add_token(SEL_END, $this->selector);
                    $this->selector = '';
                    $this->property = '';
                }
                elseif($string{$i} === ';')
                {
                    $this->property = '';
                }
                elseif($string{$i} === '\\')
                {
                    $this->property .= $this->_unicode($string,$i);
                }
            }
            elseif(!ctype_space($string{$i}))
            {
                $this->property .= $string{$i};
            }
            break;

            /* Case in-value */
            case 'iv':
            $pn = (($string{$i} === "\n" || $string{$i} === "\r") && $this->property_is_next($string,$i+1) || $i == strlen($string)-1);
            if(csstidy::is_token($string,$i) || $pn)
            {
                if($string{$i} === '/' && @$string{$i+1} === '*')
                {
                    $this->status = 'ic'; ++$i;
                    $this->from = 'iv';
                }
                elseif(($string{$i} === '"' || $string{$i} === "'" || $string{$i} === '('))
                {
                    $this->cur_string = $string{$i};
                    $this->str_char = ($string{$i} === '(') ? ')' : $string{$i};
                    $this->status = 'instr';
                    $this->from = 'iv';
                }
                elseif($string{$i} === ',')
                {
                    $this->sub_value = trim($this->sub_value).',';
                }
                elseif($string{$i} === '\\')
                {
                    $this->sub_value .= $this->_unicode($string,$i);
                }
                elseif($string{$i} === ';' || $pn)
                {
                    if($this->selector{0} === '@' && isset($at_rules[substr($this->selector,1)]) && $at_rules[substr($this->selector,1)] === 'iv')
                    {
						/* Add quotes to charset, import, namespace */
						$this->sub_value_arr[] = '"' . trim($this->sub_value) . '"';

                        $this->status = 'is';

                        switch($this->selector)
                        {
                            case '@charset': $this->charset = $this->sub_value_arr[0]; break;
                            case '@namespace': $this->namespace = implode(' ',$this->sub_value_arr); break;
                            case '@import': $this->import[] = implode(' ',$this->sub_value_arr); break;
                        }

                        $this->sub_value_arr = array();
                        $this->sub_value = '';
                        $this->selector = '';
                        $this->sel_separate = array();
                    }
                    else
                    {
                        $this->status = 'ip';
                    }
                }
                elseif($string{$i} !== '}')
                {
                    $this->sub_value .= $string{$i};
                }
                if(($string{$i} === '}' || $string{$i} === ';' || $pn) && !empty($this->selector))
                {
                    if($this->at == '')
                    {
                        $this->at = DEFAULT_AT;
                    }

                    // case settings
                    if($this->get_cfg('lowercase_s'))
                    {
                        $this->selector = strtolower($this->selector);
                    }
                    $this->property = strtolower($this->property);

                    $this->optimise->subvalue();
                    if($this->sub_value != '') {
						if (substr($this->sub_value, 0, 6) == 'format') {
							$this->sub_value = str_replace(array('format(', ')'), array('format("', '")'), $this->sub_value);
						}
                        $this->sub_value_arr[] = $this->sub_value;
                        $this->sub_value = '';
                    }

                    $this->value = implode(' ',$this->sub_value_arr);

                    $this->selector = trim($this->selector);

                    $this->optimise->value();

                    $valid = csstidy::property_is_valid($this->property);
                    if((!$this->invalid_at || $this->get_cfg('preserve_css')) && (!$this->get_cfg('discard_invalid_properties') || $valid))
                    {
                        $this->css_add_property($this->at,$this->selector,$this->property,$this->value);
                        $this->_add_token(VALUE, $this->value);
                        $this->optimise->shorthands();
                    }
                    if(!$valid)
                    {
                        if($this->get_cfg('discard_invalid_properties'))
                        {
                            $this->log('Removed invalid property: '.$this->property,'Warning');
                        }
                        else
                        {
                            $this->log('Invalid property in '.strtoupper($this->get_cfg('css_level')).': '.$this->property,'Warning');
                        }
                    }

                    $this->property = '';
                    $this->sub_value_arr = array();
                    $this->value = '';
                }
                if($string{$i} === '}')
                {
                    $this->explode_selectors();
                    $this->_add_token(SEL_END, $this->selector);
                    $this->status = 'is';
                    $this->invalid_at = false;
                    $this->selector = '';
                }
            }
            elseif(!$pn)
            {
                $this->sub_value .= $string{$i};

                if(ctype_space($string{$i}))
                {
                    $this->optimise->subvalue();
                    if($this->sub_value != '') {
                        $this->sub_value_arr[] = $this->sub_value;
                        $this->sub_value = '';
                    }
                }
            }
            break;

            /* Case in string */
            case 'instr':
            if($this->str_char === ')' && ($string{$i} === '"' || $string{$i} === '\'') && !$this->str_in_str && !csstidy::escaped($string,$i))
            {
                $this->str_in_str = true;
            }
            elseif($this->str_char === ')' && ($string{$i} === '"' || $string{$i} === '\'') && $this->str_in_str && !csstidy::escaped($string,$i))
            {
                $this->str_in_str = false;
            }
            $temp_add = $string{$i};           // ...and no not-escaped backslash at the previous position
            if( ($string{$i} === "\n" || $string{$i} === "\r") && !($string{$i-1} === '\\' && !csstidy::escaped($string,$i-1)) )
            {
                $temp_add = "\\A ";
                $this->log('Fixed incorrect newline in string','Warning');
            }
            if (!($this->str_char === ')' && in_array($string{$i}, $GLOBALS['csstidy']['whitespace']) && !$this->str_in_str)) {
                $this->cur_string .= $temp_add;
            }
            if($string{$i} == $this->str_char && !csstidy::escaped($string,$i) && !$this->str_in_str)
            {
                $this->status = $this->from;
                if (!preg_match('|[' . implode('', $GLOBALS['csstidy']['whitespace']) . ']|uis', $this->cur_string) && $this->property !== 'content') {
					if (!$this->quoted_string) {
						if ($this->str_char === '"' || $this->str_char === '\'') {
							// Temporarily disable this optimization to avoid problems with @charset rule, quote properties, and some attribute selectors...
							// Attribute selectors fixed, added quotes to @chartset, no problems with properties detected. Enabled
							$this->cur_string = substr($this->cur_string, 1, -1);
						} else if (strlen($this->cur_string) > 3 && ($this->cur_string[1] === '"' || $this->cur_string[1] === '\'')) {
							$this->cur_string = $this->cur_string[0] . substr($this->cur_string, 2, -2) . substr($this->cur_string, -1);
						}
					} else {
						$this->quoted_string = false;
					}
                }
                if($this->from === 'iv')
                {
                    $this->sub_value .= $this->cur_string;
                }
                elseif($this->from === 'is')
                {
                    $this->selector .= $this->cur_string;
                }
            }
            break;

            /* Case in-comment */
            case 'ic':
            if($string{$i} === '*' && $string{$i+1} === '/')
            {
                $this->status = $this->from;
                $i++;
                $this->_add_token(COMMENT, $cur_comment);
                $cur_comment = '';
            }
            else
            {
                $cur_comment .= $string{$i};
            }
            break;
        }
    }

    $this->optimise->postparse();

    $this->print->_reset();

    @setlocale(LC_ALL, $old); // Set locale back to original setting

    return !(empty($this->css) && empty($this->import) && empty($this->charset) && empty($this->tokens) && empty($this->namespace));
}

/**
 * Explodes selectors
 * @access private
 * @version 1.0
 */
function explode_selectors()
{
    // Explode multiple selectors
    if($this->get_cfg('merge_selectors') === 1)
    {
        $new_sels = array();
        $lastpos = 0;
        $this->sel_separate[] = strlen($this->selector);
        foreach($this->sel_separate as $num => $pos)
        {
            if($num == count($this->sel_separate)-1) {
                $pos += 1;
            }

            $new_sels[] = substr($this->selector,$lastpos,$pos-$lastpos-1);
            $lastpos = $pos;
        }

        if(count($new_sels) > 1)
        {
            foreach($new_sels as $selector)
            {
				if (isset($this->css[$this->at][$this->selector])) {
					$this->merge_css_blocks($this->at,$selector,$this->css[$this->at][$this->selector]);
				}
            }
            unset($this->css[$this->at][$this->selector]);
        }
    }
    $this->sel_separate = array();
}

/**
 * Checks if a character is escaped (and returns true if it is)
 * @param string $string
 * @param integer $pos
 * @access public
 * @return bool
 * @version 1.02
 */
static function escaped(&$string,$pos)
{
	return !(@($string{$pos-1} !== '\\') || csstidy::escaped($string,$pos-1));
}

/**
 * Adds a property with value to the existing CSS code
 * @param string $media
 * @param string $selector
 * @param string $property
 * @param string $new_val
 * @access private
 * @version 1.2
 */
function css_add_property($media,$selector,$property,$new_val)
{
    if($this->get_cfg('preserve_css') || trim($new_val) == '') {
        return;
    }

    $this->added = true;
    if(isset($this->css[$media][$selector][$property]))
    {
        if((csstidy::is_important($this->css[$media][$selector][$property]) && csstidy::is_important($new_val)) || !csstidy::is_important($this->css[$media][$selector][$property]))
        {
			// quick fix to add multiple cursor properties
			if (in_array(strtolower($property), array('cursor', 'src')))
			{
				$i = 0;
				$prop = $property;
				while (isset($this->css[$media][$selector][$prop]))
				{
					$prop = $property . '_' . ($i++);
				}
				$property = $prop;
			} else {
				unset($this->css[$media][$selector][$property]);
			}
            $this->css[$media][$selector][$property] = trim($new_val);
        }
    }
    else
    {
        $this->css[$media][$selector][$property] = trim($new_val);
    }
}

/**
 * Adds CSS to an existing media/selector
 * @param string $media
 * @param string $selector
 * @param array $css_add
 * @access private
 * @version 1.1
 */
function merge_css_blocks($media,$selector,$css_add)
{
	foreach($css_add as $property => $value)
	{
		$this->css_add_property($media,$selector,$property,$value,false);
	}
}

/**
 * Checks if $value is !important.
 * @param string $value
 * @return bool
 * @access public
 * @version 1.0
 */
static function is_important(&$value)
{
	return (!strcasecmp(substr(str_replace($GLOBALS['csstidy']['whitespace'],'',$value),-10,10),'!important'));
}

/**
 * Returns a value without !important
 * @param string $value
 * @return string
 * @access public
 * @version 1.0
 */
static function gvw_important($value)
{
	if(csstidy::is_important($value))
	{
		$value = trim($value);
		$value = substr($value,0,-9);
		$value = trim($value);
		$value = substr($value,0,-1);
		$value = trim($value);
		return $value;
	}
	return $value;
}

/**
 * Checks if the next word in a string from pos is a CSS property
 * @param string $istring
 * @param integer $pos
 * @return bool
 * @access private
 * @version 1.2
 */
function property_is_next($istring, $pos)
{
	$all_properties =& $GLOBALS['csstidy']['all_properties'];
	$istring = substr($istring,$pos,strlen($istring)-$pos);
	$pos = strpos($istring,':');
	if($pos === false)
	{
		return false;
	}
	$istring = strtolower(trim(substr($istring,0,$pos)));
	if(isset($all_properties[$istring]))
	{
		$this->log('Added semicolon to the end of declaration','Warning');
		return true;
	}
	return false;
}

/**
 * Checks if a property is valid
 * @param string $property
 * @return bool;
 * @access public
 * @version 1.0
 */
function property_is_valid($property) {
    $all_properties =& $GLOBALS['csstidy']['all_properties'];
    return (isset($all_properties[$property]) && strpos($all_properties[$property],strtoupper($this->get_cfg('css_level'))) !== false );
}


}
