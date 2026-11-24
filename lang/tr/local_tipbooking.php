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
 * Turkish strings for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['bookingcancelled'] = 'Rezervasyon iptal edildi. Zaman yeniden boş.';
$string['bookingconfirmed'] = 'Rezervasyon onaylandı. Size bir mesaj gönderdik.';
$string['bookingsofnamed'] = 'Rezervasyonlar: {$a}';
$string['cancel:confirm'] = 'Rezervasyonu iptal et';
$string['cancel:question'] = '{$a->day} günündeki {$a->range} rezervasyonunuz iptal edilsin mi? Tüm aralık başkaları için boşalır ve alanın yöneticilerine bildirim gider.';
$string['cancel:title'] = 'Rezervasyon iptali';
$string['catalogue:count'] = 'Alan sayısı: {$a}';
$string['catalogue:countondate'] = '{$a->date} için boş zamanı olan alanlar: {$a->count}';
$string['catalogue:createfirst'] = 'İlk alanı oluştur';
$string['catalogue:intro'] = 'Bir alan seçin ve boş bir zaman aralığı rezerve edin.';
$string['catalogue:mybookingshere'] = 'Buradaki rezervasyonlarınız: {$a}';
$string['catalogue:noresults'] = 'Filtrelerinize uyan alan yok';
$string['catalogue:noresults_help'] = 'Başka bir kelime deneyin veya tarih filtresini kaldırın.';
$string['catalogue:nospaces'] = 'Henüz rezerve edilebilecek alan yok';
$string['catalogue:title'] = 'Rezervasyonlar';
$string['catalogue:viewavailability'] = 'Uygunluğu gör';
$string['confirm:book'] = 'Rezervasyonu onayla';
$string['confirm:day'] = 'Gün';
$string['confirm:note'] = 'Bu, bu alan için bu güne ait tek rezervasyonunuz. Bir mesaj alacaksınız ve başlayana kadar iptal edebilirsiniz.';
$string['confirm:range'] = 'Aralık';
$string['confirm:space'] = 'Alan';
$string['confirm:title'] = 'Rezervasyonu onayla';
$string['confirm:yourtime'] = 'Sizin saatiniz';
$string['confirmdeletespace'] = '"{$a}" alanı silinsin mi? Görseli ve tüm rezervasyonları da silinir. Bu işlem geri alınamaz.';
$string['confirmdeletespacebookings'] = 'Yaklaşan rezervasyon sayısı: {$a}. Bunlar iptal edilecek ve sahiplerine bildirim gidecek.';
$string['day:closed'] = 'Kapalı';
$string['day:free'] = 'Boş: {$a}';
$string['day:full'] = 'Dolu';
$string['day:mine'] = 'Rezervasyonunuz';
$string['day:past'] = 'Geçti';
$string['deletespacenamed'] = 'Sil: {$a}';
$string['duration:hours'] = '{$a} sa';
$string['duration:minutes'] = '{$a} dk';
$string['editspace'] = 'Alanı düzenle';
$string['editspacenamed'] = 'Düzenle: {$a}';
$string['error:alreadybookedtoday'] = 'Bu gün için bu alanda zaten bir rezervasyonunuz var. Başka bir aralık seçmek için onu iptal edin.';
$string['error:cannotcancel'] = 'Bu rezervasyonu iptal edemezsiniz.';
$string['error:datetobeforefrom'] = 'Son rezervasyon günü ilk günden önce olamaz.';
$string['error:invalidadmin'] = 'Bir alanın yöneticisi yalnızca alanları yönetebilen kişiler olabilir.';
$string['error:invalidrange'] = 'Tek bir güne ait ardışık boş dilimlerden oluşan bir aralık seçin.';
$string['error:noadmins'] = 'Alan için en az bir yönetici seçin.';
$string['error:noslotfits'] = 'Çalışma saatleri en az bir dilim kadar uzun olmalıdır.';
$string['error:notactive'] = 'Bu rezervasyon zaten iptal edilmiş.';
$string['error:noweekdays'] = 'Haftanın en az bir gününü seçin.';
$string['error:rangeinpast'] = 'Bu aralık zaten başladı. Daha sonraki bir aralık seçin.';
$string['error:slottaken'] = 'Biri az önce bu aralığın bir kısmını rezerve etti. Başka bir aralık seçin.';
$string['error:spacenotavailable'] = 'Bu alan rezervasyona açık değil.';
$string['event:bookingcancelled'] = 'Rezervasyon iptal edildi';
$string['event:bookingcreated'] = 'Rezervasyon oluşturuldu';
$string['event:spacecreated'] = 'Alan oluşturuldu';
$string['event:spacedeleted'] = 'Alan silindi';
$string['event:spaceupdated'] = 'Alan güncellendi';
$string['filter:apply'] = 'Filtrele';
$string['filter:clear'] = 'Temizle';
$string['filter:clearall'] = 'Filtreleri temizle';
$string['filter:date'] = 'Boş olduğu gün';
$string['filter:from'] = 'Başlangıç';
$string['filter:search'] = 'Ara';
$string['filter:searchplaceholder'] = 'Ad veya açıklama';
$string['filter:statusactive'] = 'Etkin';
$string['filter:statusall'] = 'Tümü';
$string['filter:statuscancelled'] = 'İptal edilen';
$string['filter:to'] = 'Bitiş';
$string['filter:toggle'] = 'Filtreler';
$string['filter:userplaceholder'] = 'Ad veya e-posta';
$string['form:admins'] = 'Alanın yöneticileri';
$string['form:admins_help'] = 'Alandaki her rezervasyon ve iptal için bildirim alırlar ve alanın herhangi bir rezervasyonunu iptal edebilirler. Listede yalnızca alanları yönetebilen kişiler bulunur.';
$string['form:availability'] = 'Uygunluk';
$string['form:datefrom'] = 'Rezervasyon başlangıcı';
$string['form:dateto'] = 'Rezervasyon bitişi';
$string['form:description'] = 'Açıklama';
$string['form:durationhours'] = '{$a} sa';
$string['form:futurebookingskept'] = 'Bu alanın yaklaşan rezervasyon sayısı: {$a}. Çalışma saatlerini veya dilim uzunluğunu değiştirmek onları etkilemez.';
$string['form:general'] = 'Genel';
$string['form:hourfrom'] = 'Açılış saati';
$string['form:hours'] = 'Çalışma saatleri';
$string['form:hourto'] = 'Kapanış saati';
$string['form:image'] = 'Görsel';
$string['form:name'] = 'Ad';
$string['form:name_help'] = 'Adın her dilde görünmesi için multilang etiketleriyle yazın, örneğin: &lt;span lang="en" class="multilang"&gt;3D printer&lt;/span&gt;&lt;span lang="tr" class="multilang"&gt;3D yazıcı&lt;/span&gt;. Etiketler dahil en fazla 255 karakter.';
$string['form:noadmins'] = 'Yönetici seçilmedi';
$string['form:notifications'] = 'Bildirimler';
$string['form:slotduration'] = 'Her dilimin uzunluğu';
$string['form:slotpreview'] = 'Dilimler';
$string['form:slotpreviewleftover'] = '{$a->from} ile {$a->to} arasındaki süreye tam bir dilim sığmaz ve sunulmaz.';
$string['form:slotpreviewlist'] = 'Dilimler: {$a}.';
$string['form:slotpreviewnone'] = 'Bu çalışma saatlerine hiçbir dilim sığmıyor.';
$string['form:timezone'] = 'Saat dilimi';
$string['form:timezone_help'] = 'Çalışma saatleri ve rezervasyon günleri, her kişinin saat diliminden bağımsız olarak bu saat diliminde okunur.';
$string['form:visible'] = 'Görünür';
$string['form:visible_desc'] = 'Katalogda gösterilir';
$string['form:weekday1'] = 'Pazartesi';
$string['form:weekday2'] = 'Salı';
$string['form:weekday3'] = 'Çarşamba';
$string['form:weekday4'] = 'Perşembe';
$string['form:weekday5'] = 'Cuma';
$string['form:weekday6'] = 'Cumartesi';
$string['form:weekday7'] = 'Pazar';
$string['form:weekdays'] = 'Haftanın günleri';
$string['form:weekdayshort1'] = 'Pzt';
$string['form:weekdayshort2'] = 'Sal';
$string['form:weekdayshort3'] = 'Çar';
$string['form:weekdayshort4'] = 'Per';
$string['form:weekdayshort5'] = 'Cum';
$string['form:weekdayshort6'] = 'Cmt';
$string['form:weekdayshort7'] = 'Paz';
$string['grid:cancelmine'] = 'Rezervasyonumu iptal et';
$string['grid:closed'] = 'Bu alan bu gün rezerve edilemez.';
$string['grid:full'] = 'Bu gün için boş dilim kalmadı.';
$string['grid:mybooking'] = 'Bu gün için zaten bir rezervasyonunuz var: {$a}. Alan ve gün başına yalnızca bir rezervasyona izin verilir; başka bir aralık seçmek için bunu iptal edin.';
$string['grid:nextfree'] = 'Boş dilimi olan sonraki günü gör';
$string['grid:nonextfree'] = 'Sonraki {$a} gün içinde boş dilim yok.';
$string['hidespacenamed'] = 'Gizle: {$a}';
$string['legend:busy'] = 'Dolu';
$string['legend:free'] = 'Boş';
$string['legend:mine'] = 'Rezervasyonunuz';
$string['legend:range'] = 'Seçilen aralık';
$string['legend:unavailable'] = 'Uygun değil';
$string['managespaces'] = 'Alanları yönet';
$string['message:adminbookingcancelled:body'] = '{$a->user} kullanıcısının {$a->space} rezervasyonu ({$a->day}, {$a->from}–{$a->to}, {$a->timezone}) {$a->canceller} tarafından iptal edildi. Zaman yeniden boş.';
$string['message:adminbookingcancelled:subject'] = 'Rezervasyon iptal edildi: {$a->space}, {$a->day}';
$string['message:adminbookingcreated:body'] = '{$a->user}, {$a->space} alanını rezerve etti: {$a->day}, {$a->from}–{$a->to} ({$a->timezone}).';
$string['message:adminbookingcreated:subject'] = 'Yeni rezervasyon: {$a->space}, {$a->day}';
$string['message:bookingcancelled:body'] = '{$a->space} rezervasyonunuz ({$a->day}, {$a->from}–{$a->to}, {$a->timezone}) iptal edildi. Alanın sayfasından başka bir zaman rezerve edebilirsiniz.';
$string['message:bookingcancelled:subject'] = 'Rezervasyon iptal edildi: {$a->space}, {$a->day}';
$string['message:bookingconfirmed:body'] = '{$a->space} rezervasyonunuz ({$a->day}, {$a->from}–{$a->to}, {$a->timezone}) onaylandı. Başlayana kadar alanın sayfasından iptal edebilirsiniz.';
$string['message:bookingconfirmed:subject'] = 'Rezervasyon onaylandı: {$a->space}, {$a->day}';
$string['messageprovider:adminbookingcancelled'] = 'Yönettiğim bir alanın rezervasyonu iptal edildi';
$string['messageprovider:adminbookingcreated'] = 'Biri yönettiğim bir alanı rezerve etti';
$string['messageprovider:bookingcancelled'] = 'Rezervasyonlarımdan biri iptal edildi';
$string['messageprovider:bookingconfirmed'] = 'Rezervasyonlarımdan biri onaylandı';
$string['mybookings'] = 'Rezervasyonlarım';
$string['mybookings:cancel'] = 'İptal et';
$string['mybookings:cancelnamed'] = '{$a->space}, {$a->range} rezervasyonunu iptal et';
$string['mybookings:nopast'] = 'Geçmiş rezervasyonunuz yok';
$string['mybookings:noupcoming'] = 'Yaklaşan rezervasyonunuz yok';
$string['mybookings:noupcoming_help'] = 'Bir alan bulun ve boş bir zaman aralığı rezerve edin.';
$string['mybookings:past'] = 'Geçmiş';
$string['mybookings:seespaces'] = 'Alanları gör';
$string['mybookings:statuscancelled'] = 'İptal edildi';
$string['mybookings:statusconfirmed'] = 'Onaylandı';
$string['mybookings:statusfinished'] = 'Tamamlandı';
$string['mybookings:statusinprogress'] = 'Devam ediyor';
$string['mybookings:upcomingcount'] = 'Yaklaşan ({$a})';
$string['newspace'] = 'Yeni alan';
$string['pluginname'] = 'Alan rezervasyonu';
$string['privacy:metadata:booking'] = 'Kişilerin yaptığı alan rezervasyonları.';
$string['privacy:metadata:booking:cancelledby'] = 'İptal edildiyse rezervasyonu iptal eden kişi.';
$string['privacy:metadata:booking:spaceid'] = 'Rezerve edilen alan.';
$string['privacy:metadata:booking:status'] = 'Rezervasyonun etkin mi yoksa iptal mi edildiği.';
$string['privacy:metadata:booking:timecancelled'] = 'Rezervasyonun iptal edildiği zaman.';
$string['privacy:metadata:booking:timecreated'] = 'Rezervasyonun yapıldığı zaman.';
$string['privacy:metadata:booking:timeend'] = 'Rezerve edilen aralığın sonu.';
$string['privacy:metadata:booking:timestart'] = 'Rezerve edilen aralığın başlangıcı.';
$string['privacy:metadata:booking:userid'] = 'Rezervasyonu yapan kişi.';
$string['privacy:metadata:core_message'] = 'Bir rezervasyon yapıldığında veya iptal edildiğinde rezervasyonu yapan kişiye ve alanın yöneticilerine mesaj gönderilir.';
$string['privacy:metadata:space'] = 'Rezerve edilebilen alanlar.';
$string['privacy:metadata:space:timemodified'] = 'Alanın son değiştirildiği zaman.';
$string['privacy:metadata:space:usermodified'] = 'Alanı son değiştiren kişi.';
$string['privacy:metadata:space_admin'] = 'Her alanın, rezervasyonları hakkında bildirim alan yöneticileri.';
$string['privacy:metadata:space_admin:spaceid'] = 'Yönetilen alan.';
$string['privacy:metadata:space_admin:userid'] = 'Yönetici.';
$string['privacy:path:bookings'] = 'Rezervasyonlar';
$string['privacy:path:cancelled'] = 'Başkaları için iptal edilen rezervasyonlar';
$string['privacy:path:spaces'] = 'Alanlar';
$string['range:book'] = 'Aralığı rezerve et';
$string['range:clear'] = 'Temizle';
$string['range:hint'] = 'Aralığı genişletmek için ardışık başka bir boş dilim seçin.';
$string['range:notcontiguous'] = 'Yalnızca ardışık boş dilimleri seçin. İlkinden yeniden başlayın.';
$string['range:title'] = 'Seçilen aralık:';
$string['showspacenamed'] = 'Göster: {$a}';
$string['space:chooseday'] = 'Bir gün seçin';
$string['space:ended'] = 'Rezervasyonlar {$a} tarihinden beri kapalı.';
$string['space:nextweek'] = 'Sonraki hafta';
$string['space:prevweek'] = 'Önceki hafta';
$string['space:rule'] = 'Rezervasyonlar';
$string['space:rule_desc'] = 'Kişi başına günde bir; birkaç ardışık dilim seçebilirsiniz.';
$string['space:timesin'] = 'Saatler {$a->zone} saat diliminde (UTC{$a->offset})';
$string['space:today'] = 'Bugüne dön';
$string['space:usertime'] = 'Saat diliminiz: {$a}; onay ekranı aralığı her iki saatte de gösterir.';
$string['spacebookings:cancelnamed'] = '{$a->user}, {$a->range} rezervasyonunu iptal et';
$string['spacebookings:cancelquestion'] = '{$a->user} kullanıcısının {$a->day} günündeki {$a->range} rezervasyonu iptal edilsin mi? Aralık başkaları için boşalır ve {$a->user} bildirim alır.';
$string['spacebookings:empty'] = 'Bu alanda filtrelere uyan rezervasyon yok.';
$string['spacebookings:viewbookings'] = 'Rezervasyonları gör';
$string['spacebookings:viewpage'] = 'Alan sayfası';
$string['spacebookings:zone'] = 'Saatler alanın saat diliminde: {$a}';
$string['spacedeleted'] = '"{$a}" alanı silindi.';
$string['spacesaved'] = 'Alan kaydedildi.';
$string['summary:slots'] = '{$a} saatlik dilimler';
$string['table:admins'] = 'Yöneticiler';
$string['table:adminsmore'] = '{$a->names} ve {$a->more} kişi daha';
$string['table:bookedon'] = 'Rezervasyon tarihi';
$string['table:cancelled'] = 'İptal eden';
$string['table:day'] = 'Gün';
$string['table:days'] = 'Günler';
$string['table:futurebookings'] = 'Yaklaşan rezervasyonlar';
$string['table:hours'] = 'Çalışma saatleri';
$string['table:hoursvalue'] = '{$a->from}–{$a->to} · {$a->timezone}';
$string['table:period'] = 'Rezervasyon dönemi';
$string['table:range'] = 'Aralık';
$string['table:slot'] = 'Dilim';
$string['table:space'] = 'Alan';
$string['table:status'] = 'Durum';
$string['table:user'] = 'Kişi';
$string['table:visible'] = 'Görünür';
$string['tipbooking:book'] = 'Alan rezerve et';
$string['tipbooking:manage'] = 'Alanları ve rezervasyonlarını yönet';
$string['tipbooking:view'] = 'Rezervasyona açık alanları görüntüle';
