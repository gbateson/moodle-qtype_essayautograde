<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'qtype_essayautograde', language 'es', branch 'MOODLE_20_STABLE'
 *
 * @package    qtype
 * @subpackage essayautograde
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Ensayo (auto-calificar)';
$string['pluginname_help'] = 'En respuesta a una pregunta que puede incluir una imagen, el respondente escribe una respuesta de uno o más párrafos. Inicialmente, una calificación es otorgada automáticamente basada en el número de caracteres, palabras, oraciones o párrafos, y la presencia de ciertas frases clave. La calificación automática puede ser anulada más tarde por el profesor.';
$string['pluginname_link'] = 'question/type/essayautograde';
$string['pluginnameadding'] = 'Añadiendo una pregunta de Ensayo (auto-calificar)';
$string['pluginnameediting'] = 'Editando una pregunta de Ensayo (auto-calificar)';
$string['pluginnamesummary'] = 'Permite que un ensayo de varias oraciones o párrafos sea enviado como una respuesta a pregunta. El ensayo es calificado automáticamente. La calificación puede ser anulada más tarde.';

$string['privacy:metadata'] = 'El plugin de tipo de pregunta Ensayo (auto-calificar) no almacena ningún dato personal.';

$string['addsingleband'] = 'Añadir otra banda de calificación';
$string['addsinglephrase'] = 'Añadir otra frase clave';
$string['addmultiplebands'] = 'Añadir {$a} más bandas de calificación';
$string['addmultiplephrases'] = 'Añadir {$a} más frases clave';
$string['addpartialgrades_help'] = 'Si esta opción es habilitada, las calificaciones serán añadidas para bandas de calificación completadas.';
$string['addpartialgrades'] = '¿Otorgar calificaciones parciales?';
$string['autograding'] = 'Auto-calificar';
$string['bandcount'] = 'Para';
$string['bandpercent'] = 'o más ítems, otorgar';
$string['chars'] = 'Caracteres';
$string['charspersentence'] = 'Caracteres por oración';
$string['correctresponse'] = 'Para obtener puntaje completo para esta pregunta, Usted debe satisfacer los siguientes criterios:';
$string['enableautograde_help'] = 'Habilitar, o deshabilitar, calificación automática';
$string['enableautograde'] = 'Habilitar calificación automática';
$string['explanationcompleteband'] = '{$a->percent}% por completar Banda de calificación [{$a->gradeband}]';
$string['explanationseecomment'] = '(vea comentario debajo)';
$string['explanationdatetime'] = 'on %Y %b %d (%a) at %H:%M';
$string['explanationfirstitems'] = '{$a->percent}% por el primer(a) {$a->count} {$a->itemtype}';
$string['explanationgrade'] = 'Por lo tanto, la calificación generada por la computadora para este ensayo fue ajustada a {$a->finalgrade} = ({$a->finalpercent}% of {$a->maxgrade}).';
$string['explanationitems'] = '{$a->percent}% por {$a->count} {$a->itemtype}';
$string['explanationmaxgrade'] = 'La calificación máxima para esta pregunta es {$a->maxgrade}.';
$string['explanationnotenough'] = '{$a->count} {$a->itemtype} es menor que la calificación mínima requerida para que se le otorgue una calificación.';
$string['explanationoverride'] = 'Más tarde, {$a->datetime}, la calificación para este ensayo fue configurada manualmente a {$a->manualgrade}.';
$string['explanationpartialband'] = '{$a->percent}% por completar parcialmente la Banda de calificación [{$a->gradeband}]';
$string['explanationpenalty'] = 'Sin embargo, {$a->penaltytext} fue restado por revisar la respuesta antes del envío.';
$string['explanationrawpercent'] = 'La calificación de porcentaje crudo para este ensayo es {$a->rawpercent}% <br /> = ({$a->details}).';
$string['explanationautopercent'] = 'Esto está fuera del rango de porcentaje normal, por lo que fue ajustado a {$a->autopercent}%.';
$string['explanationremainingitems'] = '{$a->percent}% por los restantes {$a->count} {$a->itemtype}';
$string['explanationtargetphrase'] = '{$a->percent}% por incluir la frase "{$a->phrase}"';
$string['feedback'] = 'Retroalimentación';
$string['feedbackhints'] = 'Pistas para mejorar su calificación';
$string['feedbackhintphrases'] = '¿Incluyó todas las frases clave?';
$string['feedbackhintwords'] = '¿Alcanzó la meta de número de palabras?';
$string['fogindex_help'] = 'El índice de niebla de Gunning es una medida de legibilidad. Es calculado usando la siguiente fórmula.

* ((palabras por oración) + (palabras largas por oración)) x 0.4

Para más invormación, ver: <https://en.wikipedia.org/wiki/Gunning_fog_index>';
$string['fogindex'] = 'Índice de niebla';
$string['gradeband_help'] = 'Especifica el número mínimo de ítems contables para que esta banda sea aplicada, y la calificación que va a ser otorgada si esta banda es aplicada.';
$string['gradeband'] = 'Banda de calificación [{no}]';
$string['gradebands'] = 'Bandas de calificación';
$string['gradecalculation'] = 'Cálculo de calificación';
$string['gradeforthisquestion'] = 'Calificación por esta pregunta';
$string['itemcount_help'] = 'El número mínimo de ítems contables que deben estar en el texto del ensayo para obtener la calificación máxima para esta pregunta.

Tenga en cuenta, que este valor puede tornarse inefectivo por las bandas de calificación, si hubiera, definidas debajo.';
$string['itemcount'] = 'Número esperado de ítems';
$string['itemtype_help'] = 'Seleccione el tipo de ítems en el texto del ensayo que contribuirán a la auto-calificación.';
$string['itemtype'] = 'Tipo de ítems contables';
$string['lexicaldensity_help'] = 'La densidad léxica es un porcentaje calculado usando la fórmula siguiente.

* 100 x (número de palabras únicas) / (número total de palabras)

Así, un ensayo en el cual muchas palabras están repetidas tiene una densidad léxica baja, mientras que un ensayo con muchas palabras únicas tiene una alta densidad léxica.';
$string['lexicaldensity'] = 'Densidad léxica';
$string['longwords_help'] = '"Palabras largas" son palabras que tienen tres o más sílabas. Tenga en cuenta que el algoritmo para determinar el número de sílabas es muy simple y solamente proporciona resultados aproximados.';
$string['longwords'] = 'Palabras largas';
$string['longwordspersentence'] = 'Palabras largas por oración';
$string['missing'] = 'Faltante(s)';
$string['paragraphs'] = 'Párrafos';
$string['percentofquestiongrade'] = '{$a}% de la calificación de la pregunta.';
$string['phrasematch'] = 'Si';
$string['phrasepercent'] = 'es usado/a, otorgar';
$string['pleaseenterananswer'] = 'Por favor ingrese su respuesta dentro de la caja de texto.';
$string['present'] = 'Presente';
$string['rewriteresubmitwords'] = 'Re-escriba y envíe de nuevo con más palabras.';
$string['rewriteresubmitphrases'] = 'Re-escriba y envíe de nuevo incluyendo las frases faltantes.';
$string['rewriteresubmitwordsphrases'] = 'Re-escriba y envíe de nuevo con más palabras, incluyendo las frases faltantes.';
$string['sentences'] = 'Oraciones';
$string['sentencesperparagraph'] = 'Oraciones por párrafo';
$string['showcalculation_help'] = 'Si esta opción está habilitada, una explicación del cálculo de la calificación generada automáticamente será mostrada en las páginas de calificación y revisión.';
$string['showcalculation'] = '¿Mostrar cálculo de la calificación?';
$string['showfeedback_help'] = 'Si esta opción es habilitada, una tabla de retroalimentación accionable será mostrada en las páginas de calificación y revisión. Retroalimentación accionable es retroalimentación que le dice a los estudiantes lo que necesitan hacer para mejorar.';
$string['showfeedback'] = '¿Mostrar retroalimentación al estudiante?';
$string['showgradebands_help'] = 'Si esta opción es habilitada, los detalles de las bandas de calificación serán mostrados en las páginas de calificación y revisión.';
$string['showgradebands'] = '¿Mostrar bandas de calificación?';
$string['showtargetphrases_help'] = 'Si esta opción es habilitada, los detalles de las frase clave serán mostrados en las páginas de calificación y revisión.';
$string['showtargetphrases'] = '¿Mostrar frases clave?';
$string['showtextstats_help'] = 'Si esta opción es habilitada, se mostrarán estadísticas acerca del texto.';
$string['showtextstats'] = '¿Mostrar estadísticas del texto?';
$string['showtostudentsonly'] = 'Si, mostrar solamente a estudiantes';
$string['showtoteachersonly'] = 'Si, mostrar solamente a profesores';
$string['showtoteachersandstudents'] = 'Si, mostrar a profesores y a estudiantes';
$string['targetphrase_help'] = 'Especificar la calificación que será añadida si esta frase clave aparece en el ensayo.

> **por ejemplo** Si [Finalmente] es usada, otorgar [10% de la calificación de la pregunta.]

La frase clave puede ser una sola frase o una lista de frases separadas ya sea por una coma "," o la palabra "OR" (en MAYÚSCULAS).

> **por ejemplo** Si [Finalmente OR Al final] es usada, otorgar [10% de la calificación de la pregunta.]

Un signo de interrogación de cierre "?" en una frase concuerda con cualquier caracter único, mientras que un asterisco "*" concuerda con un número arbitrario de caracteres (incluyendo cero caracteres).

> **por ejemplo** Si [Primero\*Después\*Finalmente] es usado, otorgar [50% de la calificación de la pregunta.]';
$string['targetphrase'] = 'Frase clave [{no}]';
$string['targetphrases'] = 'Frases clave';
$string['textstatistics'] = 'Estadísticas del texto';
$string['textstatitems_help'] = 'Seleccione cualquier ítems aquí que Usted desea que aparezcan en las estadísticas del texto que son mostradas en las páginas para calificar y revisar.';
$string['textstatitems'] = 'Ítems estadísticos';
$string['uniquewords'] = 'Palabras únicas';
$string['words'] = 'Palabras';
$string['wordspersentence'] = 'Palabras por oración';
