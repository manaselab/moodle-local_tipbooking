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
 * Russian strings for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bookingcancelled'] = 'Бронирование отменено. Время снова свободно.';
$string['bookingconfirmed'] = 'Бронирование подтверждено. Мы отправили вам сообщение.';
$string['bookingsofnamed'] = 'Бронирования: {$a}';
$string['cancel:confirm'] = 'Отменить бронирование';
$string['cancel:question'] = 'Отменить ваше бронирование {$a->range}, {$a->day}? Весь интервал станет свободным для других, а администраторы пространства получат уведомление.';
$string['cancel:title'] = 'Отмена бронирования';
$string['catalogue:count'] = 'Пространств: {$a}';
$string['catalogue:countondate'] = 'Пространств со свободным временем на {$a->date}: {$a->count}';
$string['catalogue:createfirst'] = 'Создать первое пространство';
$string['catalogue:intro'] = 'Выберите пространство и забронируйте свободное время.';
$string['catalogue:mybookingshere'] = 'Ваших бронирований здесь: {$a}';
$string['catalogue:noresults'] = 'Нет пространств, подходящих под фильтры';
$string['catalogue:noresults_help'] = 'Попробуйте другое слово или уберите фильтр по дате.';
$string['catalogue:nospaces'] = 'Пространств для бронирования пока нет';
$string['catalogue:title'] = 'Бронирование';
$string['catalogue:viewavailability'] = 'Посмотреть свободное время';
$string['confirm:book'] = 'Подтвердить бронирование';
$string['confirm:day'] = 'День';
$string['confirm:note'] = 'Это ваше единственное бронирование этого пространства на этот день. Вы получите сообщение и сможете отменить бронирование до его начала.';
$string['confirm:range'] = 'Интервал';
$string['confirm:space'] = 'Пространство';
$string['confirm:title'] = 'Подтверждение бронирования';
$string['confirm:yourtime'] = 'Ваше время';
$string['confirmdeletespace'] = 'Удалить пространство «{$a}»? Его изображение и все бронирования тоже будут удалены. Это действие нельзя отменить.';
$string['confirmdeletespacebookings'] = 'Предстоящих бронирований: {$a}. Они будут отменены, а их владельцы получат уведомление.';
$string['day:closed'] = 'Закрыто';
$string['day:free'] = 'Свободно: {$a}';
$string['day:full'] = 'Занято';
$string['day:mine'] = 'Ваше бронирование';
$string['day:past'] = 'Прошло';
$string['deletespacenamed'] = 'Удалить {$a}';
$string['duration:hours'] = '{$a} ч';
$string['duration:minutes'] = '{$a} мин';
$string['editspace'] = 'Редактировать пространство';
$string['editspacenamed'] = 'Редактировать {$a}';
$string['error:alreadybookedtoday'] = 'У вас уже есть бронирование этого пространства на этот день. Отмените его, чтобы выбрать другой интервал.';
$string['error:cannotcancel'] = 'Вы не можете отменить это бронирование.';
$string['error:datetobeforefrom'] = 'Последний день бронирования не может быть раньше первого.';
$string['error:invalidadmin'] = 'Администраторами пространства могут быть только те, кто может управлять пространствами.';
$string['error:invalidrange'] = 'Выберите интервал из последовательных свободных слотов одного дня.';
$string['error:noadmins'] = 'Выберите хотя бы одного администратора пространства.';
$string['error:noslotfits'] = 'Часы работы должны быть не короче одного слота.';
$string['error:notactive'] = 'Это бронирование уже отменено.';
$string['error:noweekdays'] = 'Выберите хотя бы один день недели.';
$string['error:rangeinpast'] = 'Этот интервал уже начался. Выберите более поздний.';
$string['error:slottaken'] = 'Кто-то только что забронировал часть этого интервала. Выберите другой.';
$string['error:spacenotavailable'] = 'Это пространство недоступно для бронирования.';
$string['event:bookingcancelled'] = 'Бронирование отменено';
$string['event:bookingcreated'] = 'Бронирование создано';
$string['event:spacecreated'] = 'Пространство создано';
$string['event:spacedeleted'] = 'Пространство удалено';
$string['event:spaceupdated'] = 'Пространство изменено';
$string['filter:apply'] = 'Фильтровать';
$string['filter:clear'] = 'Сбросить';
$string['filter:clearall'] = 'Сбросить фильтры';
$string['filter:date'] = 'Свободно в день';
$string['filter:from'] = 'С';
$string['filter:search'] = 'Поиск';
$string['filter:searchplaceholder'] = 'Название или описание';
$string['filter:statusactive'] = 'Активные';
$string['filter:statusall'] = 'Все';
$string['filter:statuscancelled'] = 'Отменённые';
$string['filter:to'] = 'По';
$string['filter:toggle'] = 'Фильтры';
$string['filter:userplaceholder'] = 'Имя или эл. почта';
$string['form:admins'] = 'Администраторы пространства';
$string['form:admins_help'] = 'Они получают уведомления о каждом бронировании и отмене в этом пространстве и могут отменить любое его бронирование. В списке только те, кто может управлять пространствами.';
$string['form:availability'] = 'Доступность';
$string['form:datefrom'] = 'Бронирование с';
$string['form:dateto'] = 'Бронирование до';
$string['form:description'] = 'Описание';
$string['form:durationhours'] = '{$a} ч';
$string['form:futurebookingskept'] = 'У этого пространства предстоящих бронирований: {$a}. Изменение часов работы или длины слота их не затрагивает.';
$string['form:general'] = 'Общее';
$string['form:hourfrom'] = 'Время открытия';
$string['form:hours'] = 'Часы работы';
$string['form:hourto'] = 'Время закрытия';
$string['form:image'] = 'Изображение';
$string['form:name'] = 'Название';
$string['form:name_help'] = 'Чтобы название отображалось на каждом языке, напишите его с тегами multilang, например: &lt;span lang="en" class="multilang"&gt;3D printer&lt;/span&gt;&lt;span lang="ru" class="multilang"&gt;3D-принтер&lt;/span&gt;. До 255 символов вместе с тегами.';
$string['form:noadmins'] = 'Администраторы не выбраны';
$string['form:notifications'] = 'Уведомления';
$string['form:slotduration'] = 'Длина слота';
$string['form:slotpreview'] = 'Слоты';
$string['form:slotpreviewleftover'] = 'Время с {$a->from} до {$a->to} не вмещает целый слот и не будет предложено.';
$string['form:slotpreviewlist'] = 'Слоты: {$a}.';
$string['form:slotpreviewnone'] = 'В эти часы работы не помещается ни один слот.';
$string['form:timezone'] = 'Часовой пояс';
$string['form:timezone_help'] = 'Часы работы и дни бронирования указываются в этом часовом поясе, независимо от часового пояса каждого пользователя.';
$string['form:visible'] = 'Видимое';
$string['form:visible_desc'] = 'Показывать в каталоге';
$string['form:weekday1'] = 'Понедельник';
$string['form:weekday2'] = 'Вторник';
$string['form:weekday3'] = 'Среда';
$string['form:weekday4'] = 'Четверг';
$string['form:weekday5'] = 'Пятница';
$string['form:weekday6'] = 'Суббота';
$string['form:weekday7'] = 'Воскресенье';
$string['form:weekdays'] = 'Дни недели';
$string['form:weekdayshort1'] = 'Пн';
$string['form:weekdayshort2'] = 'Вт';
$string['form:weekdayshort3'] = 'Ср';
$string['form:weekdayshort4'] = 'Чт';
$string['form:weekdayshort5'] = 'Пт';
$string['form:weekdayshort6'] = 'Сб';
$string['form:weekdayshort7'] = 'Вс';
$string['grid:cancelmine'] = 'Отменить моё бронирование';
$string['grid:closed'] = 'В этот день пространство нельзя забронировать.';
$string['grid:full'] = 'В этот день свободных слотов не осталось.';
$string['grid:mybooking'] = 'У вас уже есть бронирование на этот день: {$a}. Разрешено одно бронирование на пространство в день; чтобы выбрать другой интервал, отмените текущее.';
$string['grid:nextfree'] = 'Ближайший день со свободными слотами';
$string['grid:nonextfree'] = 'В ближайшие {$a} дней свободных слотов нет.';
$string['hidespacenamed'] = 'Скрыть {$a}';
$string['legend:busy'] = 'Занято';
$string['legend:free'] = 'Свободно';
$string['legend:mine'] = 'Ваше бронирование';
$string['legend:range'] = 'Выбранный интервал';
$string['legend:unavailable'] = 'Недоступно';
$string['managespaces'] = 'Управление пространствами';
$string['message:adminbookingcancelled:body'] = 'Бронирование {$a->space} пользователем {$a->user} на {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), отменил(а) {$a->canceller}. Время снова свободно.';
$string['message:adminbookingcancelled:subject'] = 'Бронирование отменено: {$a->space}, {$a->day}';
$string['message:adminbookingcreated:body'] = '{$a->user} забронировал(а) {$a->space} на {$a->day}, {$a->from}–{$a->to} ({$a->timezone}).';
$string['message:adminbookingcreated:subject'] = 'Новое бронирование: {$a->space}, {$a->day}';
$string['message:bookingcancelled:body'] = 'Ваше бронирование {$a->space} на {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), отменено. Вы можете забронировать другое время на странице пространства.';
$string['message:bookingcancelled:subject'] = 'Бронирование отменено: {$a->space}, {$a->day}';
$string['message:bookingconfirmed:body'] = 'Ваше бронирование {$a->space} на {$a->day}, {$a->from}–{$a->to} ({$a->timezone}), подтверждено. Вы можете отменить его на странице пространства до его начала.';
$string['message:bookingconfirmed:subject'] = 'Бронирование подтверждено: {$a->space}, {$a->day}';
$string['messageprovider:adminbookingcancelled'] = 'Отменено бронирование пространства, которым я управляю';
$string['messageprovider:adminbookingcreated'] = 'Кто-то бронирует пространство, которым я управляю';
$string['messageprovider:bookingcancelled'] = 'Одно из моих бронирований отменено';
$string['messageprovider:bookingconfirmed'] = 'Одно из моих бронирований подтверждено';
$string['mybookings'] = 'Мои бронирования';
$string['mybookings:cancel'] = 'Отменить';
$string['mybookings:cancelnamed'] = 'Отменить бронирование {$a->space}, {$a->range}';
$string['mybookings:nopast'] = 'У вас нет прошедших бронирований';
$string['mybookings:noupcoming'] = 'У вас нет предстоящих бронирований';
$string['mybookings:noupcoming_help'] = 'Найдите пространство и забронируйте свободное время.';
$string['mybookings:past'] = 'Прошедшие';
$string['mybookings:seespaces'] = 'Посмотреть пространства';
$string['mybookings:statuscancelled'] = 'Отменено';
$string['mybookings:statusconfirmed'] = 'Подтверждено';
$string['mybookings:statusfinished'] = 'Завершено';
$string['mybookings:statusinprogress'] = 'Идёт сейчас';
$string['mybookings:upcomingcount'] = 'Предстоящие ({$a})';
$string['newspace'] = 'Новое пространство';
$string['pluginname'] = 'Бронирование пространств';
$string['privacy:metadata:booking'] = 'Бронирования пространств, сделанные пользователями.';
$string['privacy:metadata:booking:cancelledby'] = 'Пользователь, отменивший бронирование, если оно было отменено.';
$string['privacy:metadata:booking:spaceid'] = 'Забронированное пространство.';
$string['privacy:metadata:booking:status'] = 'Активно бронирование или отменено.';
$string['privacy:metadata:booking:timecancelled'] = 'Когда бронирование было отменено.';
$string['privacy:metadata:booking:timecreated'] = 'Когда было сделано бронирование.';
$string['privacy:metadata:booking:timeend'] = 'Конец забронированного интервала.';
$string['privacy:metadata:booking:timestart'] = 'Начало забронированного интервала.';
$string['privacy:metadata:booking:userid'] = 'Пользователь, сделавший бронирование.';
$string['privacy:metadata:core_message'] = 'Сообщения отправляются тому, кто бронирует, и администраторам пространства, когда бронирование создаётся или отменяется.';
$string['privacy:metadata:space'] = 'Пространства для бронирования.';
$string['privacy:metadata:space:timemodified'] = 'Когда пространство было изменено в последний раз.';
$string['privacy:metadata:space:usermodified'] = 'Пользователь, последним изменивший пространство.';
$string['privacy:metadata:space_admin'] = 'Администраторы каждого пространства, получающие уведомления о его бронированиях.';
$string['privacy:metadata:space_admin:spaceid'] = 'Пространство, которым управляют.';
$string['privacy:metadata:space_admin:userid'] = 'Администратор.';
$string['privacy:path:bookings'] = 'Бронирования';
$string['privacy:path:cancelled'] = 'Бронирования других людей, отменённые вами';
$string['privacy:path:spaces'] = 'Пространства';
$string['range:book'] = 'Забронировать интервал';
$string['range:clear'] = 'Сбросить';
$string['range:hint'] = 'Выберите ещё один соседний свободный слот, чтобы расширить интервал.';
$string['range:notcontiguous'] = 'Выбирайте только последовательные свободные слоты. Начните заново с первого.';
$string['range:title'] = 'Выбранный интервал:';
$string['showspacenamed'] = 'Показать {$a}';
$string['space:chooseday'] = 'Выберите день';
$string['space:ended'] = 'Бронирование закрыто с {$a}.';
$string['space:nextweek'] = 'Следующая неделя';
$string['space:prevweek'] = 'Предыдущая неделя';
$string['space:rule'] = 'Бронирование';
$string['space:rule_desc'] = 'Одно на человека в день; можно выбрать несколько последовательных слотов.';
$string['space:timesin'] = 'Время указано в поясе {$a->zone} (UTC{$a->offset})';
$string['space:today'] = 'Вернуться к сегодняшнему дню';
$string['space:usertime'] = 'Ваш часовой пояс: {$a}; при подтверждении интервал будет показан в обоих поясах.';
$string['spacebookings:cancelnamed'] = 'Отменить бронирование пользователя {$a->user}, {$a->range}';
$string['spacebookings:cancelquestion'] = 'Отменить бронирование пользователя {$a->user}, {$a->range}, {$a->day}? Интервал станет свободным для других, а {$a->user} получит уведомление.';
$string['spacebookings:empty'] = 'У этого пространства нет бронирований, подходящих под фильтры.';
$string['spacebookings:viewbookings'] = 'Бронирования';
$string['spacebookings:viewpage'] = 'Страница пространства';
$string['spacebookings:zone'] = 'Время указано в часовом поясе пространства: {$a}';
$string['spacedeleted'] = 'Пространство «{$a}» удалено.';
$string['spacesaved'] = 'Пространство сохранено.';
$string['summary:slots'] = 'Слоты по {$a} ч';
$string['table:admins'] = 'Администраторы';
$string['table:adminsmore'] = '{$a->names} и ещё {$a->more}';
$string['table:bookedon'] = 'Дата бронирования';
$string['table:cancelled'] = 'Кем отменено';
$string['table:day'] = 'День';
$string['table:days'] = 'Дни';
$string['table:futurebookings'] = 'Предстоящие бронирования';
$string['table:hours'] = 'Часы работы';
$string['table:hoursvalue'] = '{$a->from}–{$a->to} · {$a->timezone}';
$string['table:period'] = 'Период бронирования';
$string['table:range'] = 'Интервал';
$string['table:slot'] = 'Слот';
$string['table:space'] = 'Пространство';
$string['table:status'] = 'Состояние';
$string['table:user'] = 'Пользователь';
$string['table:visible'] = 'Видимое';
$string['tipbooking:book'] = 'Бронировать пространства';
$string['tipbooking:manage'] = 'Управлять пространствами и их бронированиями';
$string['tipbooking:view'] = 'Просматривать пространства, доступные для бронирования';
