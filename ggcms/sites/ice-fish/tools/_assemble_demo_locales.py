# -*- coding: utf-8 -*-
"""One-off assembler for icefish_demo_full_locales.py"""
from __future__ import annotations

import re
from pathlib import Path

TOOLS = Path(__file__).resolve().parent
OUT = TOOLS / "icefish_demo_full_locales.py"

PIECES = [
    ("fr", "_piece_fr_gen.py", "_fr"),
    ("de", "_piece_de_gen.py", "_de"),
    ("es", "_piece_es.py", "_es"),
    ("pt", "_demo_locale_frag_01_pt.py.txt", "_pt"),
    ("it", "_demo_locale_frag_02_it.py.txt", "_it"),
    ("nl", "_piece_nl_gen.py", "_nl"),
    ("pl", "_piece_pl_gen.py", "_pl"),
    ("ua", "_piece_ua_gen.py", "_ua"),
    ("hi", "_piece_hi_gen.py", "_hi"),
    ("bn", "_piece_bn_gen.py", "_bn"),
    ("ar", "_piece_ar_gen.py", "_ar"),
]


def _load(fn_name: str, path: str) -> dict:
    ns: dict = {}
    exec((TOOLS / path).read_text(encoding="utf-8"), ns)
    return ns[fn_name]()


def _az() -> dict:
    return {
        "intro_h2": "Ice Fish Demo — risksiz pulsuz oynayın",
        "intro_paras": [
            "Ice Fish populyar oldu, çünki başlanğıcda oyunçudan çox düşünməyə məcbur etmir. Oyunu açırsınız və ideyanı demək olar ki, dərhal başa düşürsünüz. Klassik slot xaosu, fırlanan barabanlar və ya çaşdırıcı simvollar yoxdur. Kiçik arkadaya bənzəyir, amma hər növbəti addım yenə də risk daşıyır.",
            "Məhz buna görə Ice Fish bu qədər yaxşı işləyir. Klassik kazino oyunundan çox arkadaya oxşayır. Toyuğu, yolu, maşınları görürsünüz və əsas qərar həmişə aydındır: cari nəticəni götürmək və ya bir addım daha cəhd etmək.",
            "InOut Games müxtəlif oyunçu tiplərini cəlb edən elementləri bir araya gətirdi. Oyun yüngül və əyləncəli görünür, qaydalar sadədir. Başa düşmək uzun çəkmir, amma oynamağa başladıqdan sonra diqqətinizi asanlıqla saxlayır.",
            "Saytımızdakı pulsuz versiya məhz bunun üçündür — Ice Fish-u özünüz sınamaq üçün. Demonu açın, virtual balansla oynayın, mexanikanı yoxlayın və bir sent xərcləmədən oyundan həzz alın.",
            "Real pul oyununa keçməzdən əvvəl bu oyunun niyə bu qədər populyar olduğunu anlamaq üçün yaxşı yoldur.",
        ],
        "what_h2": "Ice Fish demo rejimi nədir?",
        "what_paras": [
            "Ice Fish Demo oyunun pulsuz versiyasıdır. Real pul deyil, virtual balansla oynayırsınız. Əsas məqam budur. Oyunu aça, test mərci qoya, çətinlik səviyyəsi seçə və toyuğun yol boyu necə irəlilədiyini öz vəsaitinizi itirmək qorxusu olmadan görə bilərsiniz. Səhv addım etsəniz, ciddi heç nə olmur — yalnız demo kreditlər itir və yenidən başlayırsınız.",
            "Demo başqa oyun deyil. Real versiyaya demək olar ki, eyni işləyir. Toyuq addım-addım irəliləyir, hər təhlükəsiz addımdan sonra multiplikator artır və nə vaxt dayanacağınıza qərar verməlisiniz. İndi qazanını götürmək və ya bir addım daha — Ice Fish-un bütün gərginliyi budur.",
            "Yeni başlayanlar üçün ən yaxşı başlanğıcdır. Depozit, qeydiyyat və ya balans narahatlığı lazım deyil. Cash Out düyməsinin necə işlədiyini yoxlaya və raundun nə qədər tez təhlükəsizdən itirilmişə keçə biləcəyini görə bilərsiniz.",
            "Vacibdir: demo faydalıdır, amma sizi oyundan daha güclü etmir. Virtual kreditlərlə bir neçə uğurlu raund qazanma sistemi tapdığınız demək deyil. Demoda adətən daha azad oynayırlar: daha çox risk edirlər, daha uzun gözləyirlər və itirməyə az qayğı göstərirlər.",
            "Real pul hər şeyi dəyişir. Eyni addım öz balansınız olanda fərqli hiss olunur. Ice Fish Demo-nu məhz onun güclü olduğu üçün istifadə edin — oyunu öyrənmək, düymələri sınamaq və real mərc haqqında düşünməzdən əvvəl riski başa düşmək.",
        ],
        "start_h2": "Ice Fish Demo-ya necə başlamaq olar",
        "start_steps": [
            "Ice Fish demosuna başlamaq çox sadədir. İstəsəniz tətbiqi yükləyib quraşdırın və quraşdırma bitəndə açın. Həmçinin saytımızın səhifəsində brauzerdə birbaşa oynaya bilərsiniz.",
            "Başlamazdan əvvəl çətinlik səviyyəsini seçin. Yalnız oyunu başa düşmək istəyirsinizsə Easy ilə başlayın və ya riskin necə dəyişdiyini görmək üçün sonra daha yüksək rejimlərə keçin.",
            "Sonra virtual mərc təyin edin ki, müxtəlif məbləğləri sınayasınız və raundda multiplikatorun necə işlədiyini görəsiniz.",
            "Toyuğu addım-addım irəlilədin. Addım təhlükəsizdirsə, multiplikator artır. İstənilən anda Cash Out basıb cari virtual qazancı götürə bilərsiniz.",
        ],
        "start_summary": "Bütün proses sadədir: demonu açın, rejimi seçin, mərci təyin edin, irəliləyin və risk kifayət qədər hiss olunduqda dayanın.",
        "vs_h2": "Demo və real pul oyunu",
        "vs_paras": [
            "Oyunun özü əslində dəyişmir. Eyni toyuq, eyni yol, eyni düymələr, eyni ideya. Fərq balansdadır.",
            "Demoda virtual kreditlərlə oynayırsınız. Onları itirə, yenidən başlaya, başqa çətinlik sınaya bilərsiniz — öz pulunuza toxunulmur. Ona görə demo daha azad oynanılır: əlavə addımlar atılır, daha yüksək risk sınanır və nə olacağını görmək üçün «Go» basılır.",
            "Real pulla hiss fərqlidir. Eyni kiçik qərar belə daha ağır ola bilər. Mərc, itki və geri qazanma ehtimalı haqqında düşünürsünüz. Bəziləri tez Cash Out edir, bəziləri daha böyük nəticə üçün çox irəliləyir.",
            "Texniki fərq sadədir: demoda virtual balans, real pul rejimində isə real vəsait. Amma emosional olaraq bu eyni oyun deyil. Demo öyrənmək üçündür. Real pul təzyiq əlavə edir və təzyiq qərarları tez dəyişir.",
        ],
        "why_h2": "Niyə əvvəlcə demodan başlamaq lazımdır?",
        "why_before": [
            "Demo real pul istifadə etməzdən əvvəl Ice Fish-u başa düşməyin ən təhlükəsiz yoludur. Orada səhv edə bilərsiniz — məhz bunun üçündür.",
            "Pulsuz rejimdə əsas mexanikanı təzyiq olmadan sınaya bilərsiniz. Çətinlik seçin, virtual mərc qoyun, toyuğu irəlilədin və raundun necə hiss olunduğunu görün. Bir neçə cəhddən sonra oyun daha aydın olur.",
        ],
        "why_bullets_intro": "Demo bir neçə məsələdə kömək edir:",
        "why_bullets": [
            "oyunun necə işlədiyini başa düşürsünüz;",
            "çətinliyin riski necə dəyişdirdiyini görürsünüz;",
            "Cash Out düyməsinə öyrəşirsiniz;",
            "öz oyun tərzinizi sınaya bilərsiniz;",
            "riskin nə qədər tez böyüdüyünü fərq edirsiniz;",
            "real balansınız barədə narahat olmadan oynayırsınız.",
        ],
        "why_after": [
            "Öz davranışınızı yoxlamaq üçün də yaxşı yerdir. Bəziləri tez cash out edir, bəziləri bir addım daha basır, bəziləri nə olacağını görmək üçün birbaşa Hardcore-a gedir. Demoda bunların hamısı normaldır, çünki balans virtualdır.",
            "Real pul oyunu fərqlidir. Orada eyni qərar daha ağır hiss olunur. Risk etməzdən əvvəl demoda vaxt keçirib oyunu düzgün başa düşmək məntiqlidir.",
        ],
        "mobile_h2": "Mobil cihazda Ice Fish Demo",
        "mobile_paras": [
            "Ice Fish demo telefonda problemsiz işləyir. Oyun sadədir, böyük ekran lazım deyil. Açırsınız, toyuğu, yolu, mərc məbləğini və əsas düymələri görürsünüz — kifayətdir.",
            "Mobildə ekran şaquli olduğuna görə oyun bir az fərqli görünür. Yol masaüstü qədər geniş deyil. Bəzi elementlər yaxınlaşdırılıb, çətinlik menyusu adətən bir düymədə gizlənir.",
            "Düymələr yenə də rahatdır. Virtual mərc təyin edin, Play basın, toyuğu irəlilədin və növbəti addımı risk etmək istəmədikdə cash out edin.",
            "Yüklənmə telefon və internetdən asılıdır. Yeni cihaz demoni daha tez açar. Köhnə telefon və ya zəif siqnal bir az yavaşladı bilər. Amma hər saniyə panik basmalı olduğunuz oyun deyil — təhlükəsiz addımdan sonra dayanıb düşünə bilərsiniz.",
            "Demonu birbaşa brauzerdən oynaya bilərsiniz. Tətbiq məcburi deyil, amma daha sürətli giriş üçün saytımızdakı App qısayolundan istifadə edin. Ice Fish Demo ikonunu əsas ekrana qoyur, növbəti dəfə səhifə axtarmalı olmursunuz.",
        ],
        "download_h2": "Ice Fish Demo yükləmək olarmı?",
        "download_paras": [
            "Oynamaq üçün Ice Fish Demo-nu yükləmək lazım deyil. Demo veb üzərindən işləyir — brauzerdən açıb virtual balansla dərhal oynaya bilərsiniz.",
            "Saytımızda tətbiq tipli qısayol və ya PWA ilə sürətli giriş də ola bilər. Telefonda tətbiq kimi görünür, amma demo yenə sayt üzərindən işləyir. Məqsəd rahatlıqdır: ikona toxunub Ice Fish-u daha tez açmaq.",
            "Digər saytlardan təsadüfi APK fayllarına diqqətli olun. «Xüsusi Ice Fish APK», «hack versiya» və ya «predictor app» təklif edən səhifələri keçin. Bu fayllar saxta ola bilər və bəzən yalnız məlumat toplamaq üçündür.",
            "Adi demo oyunu üçün brauzer versiyası kifayətdir. Səhifəni açın, oyunu işə salın və riskli quraşdırma olmadan Ice Fish-u sınayın.",
        ],
        "trouble_h2": "Ice Fish Demo işə düşmirsə",
        "trouble_paras": [
            "Bəzən demo sadəcə başlamır. Böyük sirr yoxdur — çox vaxt brauzer və ya internetdən olur, oyundan deyil.",
            "Sadə həll ilə başlayın: səhifəni yeniləyin. Yükləyici donubsa, tabı bağlayıb yenidən açın. Hələ də işləmirsə, başqa brauzer sınayın. Chrome, Safari, Firefox, Samsung Internet — adətən biri daha yaxşıdır.",
            "Play düyməsi görünür, amma heç nə olmursa, bir az gözləyin. Düymə oyun tam yüklənməmiş görünə bilər. Bu mobil internetdə və ya köhnə telefonlarda daha çox olur.",
            "Telefon donmaları da adətən sadədir. Cihaz sistemini yükləyən nə varsa yoxlayın.",
            "Ekranda virtual balans yoxdur? Sağ yuxarı küncə diqqətlə baxın — balans orada göstərilməlidir.",
            "Reklam bloklayıcıları da səhifəni poza bilər. Bəziləri oyun skriptlərini səhvən bloklayır. Bu səhifə üçün bloklayıcı söndürün və ya gizli rejim sınayın. İnterneti də yoxlayın. Demo ağır deyil, amma zəif siqnal ilk raunddan əvvəl asılı qala bilər.",
        ],
        "safety_h2": "Demo təhlükəsizliyi və ədalətli oyun",
        "safety_paras": [
            "Ice Fish Demo bir məqsəd üçün yaradılıb — real pul istifadə etməzdən əvvəl oyunu sınamaq. Açırsınız, virtual balansla oynayırsınız, düymələri yoxlayırsınız və raundun necə işlədiyini görürsünüz. Demo yalnız bunu etməlidir.",
            "Pulsuz versiya üçün kart nömrəsi, CVV, SMS təsdiqi, pasport məlumatı və ya ödəniş məlumatı tələb olunmamalıdır. «Demo rejimi» açmadan depozit istəyən sayt artıq pis işarədir.",
            "Saytımızdakı demo məşq və oyunla tanışlıq üçündür. Mexanikanı, çətinlik səviyyələrini və multiplikatorun təhlükəsiz addımdan sonra necə dəyişdiyini yoxlaya bilərsiniz. Bunun üçün real ödəniş lazım deyil.",
            "Ice Fish adını istifadə edib «zəmanətli qazanclar», «gizli proqnoz» və ya «real ödənişli xüsusi demo» vəd edən səhifələrə diqqətli olun. Adi demo belə işləmir — virtual kredit istifadə edir və nəticə demoda qalır.",
            "Sadə qayda: əgər həqiqətən pulsuz demosudursa, pul istəməməlidir.",
        ],
        "faq_h2": "FAQ — Ice Fish Demo",
        "faq": [
            ("Ice Fish Demo-nu pulsuz oynaya bilərəmmi?", "Bəli. Saytımızdakı demo pulsuzdur və real pul risk etmirsiniz."),
            ("Qeydiyyat lazımdır?", "Xeyr. Oyunu sınamaq üçün qeydiyyat tələb olunmur."),
            ("Demoda real pul qazana bilərəmmi?", "Xeyr. Demo qazancları demoda qalır. Virtual balansı artıra, itirə, yenidən başlaya bilərsiniz — amma heç nə çıxara bilməzsiniz."),
            ("Demo rejimi real oyuna bənzəyirmi?", "Oyun eynidir. Eyni toyuq, yol, çətinlik səviyyələri, eyni cash out ideyası. Fərq balansdadır: demoda virtual, real pul rejimində öz pulunuz."),
            ("Ice Fish Demo mobil işləyirmi?", "Tətbiq xüsusilə mobil versiya nəzərə alınaraq hazırlanıb."),
            ("Nəsə yükləmək lazımdır?", "Xeyr. Demo brauzerdə oynanılır. Daha sürətli giriş üçün saytımızdakı qısayoldan istifadə edə bilərsiniz, amma məcburi deyil."),
            ("Növbəti addım proqnozlaşdırıla bilərmi?", "Xeyr. Növbəti hərəkətdə nə olacağını əvvəlcədən bilmək olmaz. «Ice Fish proqnozları» vəd edən app və ya saytlara ehtiyatla yanaşın."),
            ("Demo rejimi üçün strategiya varmı?", "Demoda müxtəlif üslubları sınaya bilərsiniz: tez cash out, yalnız Easy və ya daha yüksək çətinlik. Amma demo zəmanətli sistem açmır."),
            ("Bu rəsmi Ice Fish oyunudur?", "Ice Fish InOut Games tərəfindən hazırlanıb. Saytımızdakı demo məşq üçündür. Saytımız InOut Games-in rəsmi saytı deyil."),
        ],
        "titles": {
            "start_steps_title": "Dörd addımda necə başlamaq olar",
            "step1_h": "1. Açın və çətinlik seçin",
            "step1_p": "Demoyu brauzerdə və ya tətbiqdə işə salın və başlamaq üçün rejim seçin.",
            "step2_h": "2. Mərci təyin edin",
            "step2_p": "Raundda multiplikatoru sınamaq üçün virtual mərc təyin edin.",
            "step3_h": "3. Cash Out",
            "step3_p": "Təhlükəsiz addımdan sonra cash out edin və cari multiplikatoru saxlayın.",
        },
        "alts": {
            "gameplay": "Ekranda multiplikatorla Ice Fish demo gameplay",
            "app": "App və ya brauzer vasitəsilə Ice Fish demo girişi",
            "mobile": "Portret rejimində mobil Ice Fish Demo",
            "interface": "Müxtəlif cihazlarda Ice Fish demo interfeysi",
            "step1": "Addım 1 — Ice Fish demosunu açın və çətinlik seçin",
            "step2": "Addım 2 — Ice Fish Demo-da virtual mərc təyin edin",
            "step3": "Addım 3 — təhlükəsiz addımdan sonra Ice Fish Demo-da Cash Out",
        },
    }


def _vi() -> dict:
    return {
        "intro_h2": "Ice Fish Demo — chơi miễn phí không rủi ro",
        "intro_paras": [
            "Ice Fish trở nên phổ biến vì lúc bắt đầu bạn không phải suy nghĩ quá nhiều. Mở game là hiểu ý tưởng gần như ngay lập tức. Không có hỗn loạn slot cổ điển, không guồng quay, không biểu tượng rối. Trông giống arcade nhỏ, nhưng mỗi bước tiếp theo vẫn mang rủi ro.",
            "Đó chính là lý do Ice Fish hoạt động tốt. Cảm giác giống game arcade hơn là tựa casino cổ điển. Bạn thấy gà, đường, xe — và quyết định chính luôn rõ: giữ kết quả hiện tại hay thử thêm một bước.",
            "InOut Games đã gom những gì thu hút nhiều kiểu người chơi. Game trông nhẹ, vui, luật đơn giản. Không mất lâu để hiểu, nhưng một khi bắt đầu rất dễ giữ chú ý của bạn.",
            "Phiên bản miễn phí trên site của chúng tôi dành đúng cho việc đó — để bạn tự thử Ice Fish. Mở demo, chơi bằng số dư ảo, thử cơ chế và tận hưởng mà không tốn một xu.",
            "Đó là cách hay để hiểu vì sao game này nổi tiếng trước khi nghĩ đến chơi tiền thật.",
        ],
        "what_h2": "Chế độ demo Ice Fish là gì?",
        "what_paras": [
            "Ice Fish Demo là phiên bản miễn phí của game. Bạn chơi bằng số dư ảo, không phải tiền thật. Đó là điểm chính. Bạn có thể mở game, đặt cược thử, chọn độ khó và xem gà đi trên đường mà không lo mất tiền của mình. Sai một nước cũng không sao — chỉ mất credit demo và bắt đầu lại.",
            "Demo không phải game khác. Hoạt động gần như bản thật. Gà vẫn đi từng bước, hệ số nhân tăng sau mỗi bước an toàn, và bạn vẫn phải quyết định khi nào dừng. Rút tiền ngay hay thử thêm một bước — đó là toàn bộ áp lực của Ice Fish.",
            "Với người mới, đây là cách bắt đầu tốt nhất. Không cần nạp, đăng ký hay lo số dư. Bạn có thể kiểm tra nút Cash Out và thấy vòng chơi có thể từ an toàn chuyển sang thua nhanh thế nào.",
            "Quan trọng: demo hữu ích nhưng không làm bạn giỏi hơn game. Vài vòng tốt với credit ảo không có nghĩa bạn tìm ra hệ thống thắng. Ở demo người ta thường chơi thoải mái hơn: mạo hiểm hơn, chờ lâu hơn và ít lo thua.",
            "Tiền thật thay đổi mọi thứ. Cùng một bước cảm giác khác khi số dư của bạn đang stake. Dùng Ice Fish Demo đúng việc nó giỏi — học game, thử nút và hiểu rủi ro trước khi nghĩ cược thật.",
        ],
        "start_h2": "Cách bắt đầu Ice Fish Demo",
        "start_steps": [
            "Bắt đầu demo Ice Fish khá đơn giản. Tải và cài app nếu muốn, mở sau khi cài xong. Hoặc chơi trực tiếp trên trình duyệt ở trang site của chúng tôi.",
            "Trước khi bắt đầu, chọn độ khó. Bắt đầu Easy nếu chỉ muốn hiểu game, hoặc chuyển chế độ cao hơn sau để xem rủi ro thay đổi thế nào.",
            "Sau đó đặt cược ảo để thử các mức khác nhau và xem hệ số nhân hoạt động trong vòng.",
            "Cho gà tiến từng bước. Bước an toàn thì hệ số nhân tăng. Bất cứ lúc nào bạn có thể nhấn Cash Out và lấy thắng ảo hiện tại.",
        ],
        "start_summary": "Toàn bộ quy trình đơn giản: mở demo, chọn chế độ, đặt cược, tiến lên và dừng khi cảm thấy rủi ro đủ.",
        "vs_h2": "Demo và chơi tiền thật",
        "vs_paras": [
            "Bản thân game hầu như không đổi. Cùng con gà, đường, nút, ý tưởng. Khác ở số dư.",
            "Ở demo bạn chơi bằng credit ảo. Có thể thua, khởi động lại, thử độ khó khác — tiền của bạn không bị ảnh hưởng. Vì vậy người ta thường chơi demo thoáng hơn: thêm bước, thử rủi ro cao hơn, nhấn «Go» chỉ để xem chuyện gì xảy ra.",
            "Với tiền thật cảm giác khác. Cùng quyết định nhỏ cũng có thể nặng hơn. Bạn nghĩ về cược, thua, cơ hội gỡ. Một số cash out quá sớm vì lo. Người khác đi quá xa vì muốn kết quả lớn hơn.",
            "Khác biệt kỹ thuật đơn giản: số dư ảo ở demo, tiền thật ở chế độ tiền thật. Nhưng về cảm xúc không phải cùng một game. Demo để học. Chơi tiền thật thêm áp lực, và áp lực đổi quyết định rất nhanh.",
        ],
        "why_h2": "Vì sao nên bắt đầu bằng demo?",
        "why_before": [
            "Demo là cách an toàn nhất để hiểu Ice Fish trước khi dùng tiền thật. Bạn có thể sai ở đó — đó là mục đích.",
            "Ở chế độ miễn phí bạn thử cơ chế cơ bản không áp lực. Chọn độ khó, đặt cược ảo, cho gà tiến và cảm nhận vòng chơi. Sau vài lần game rõ hơn nhiều.",
        ],
        "why_bullets_intro": "Demo giúp vài việc:",
        "why_bullets": [
            "hiểu game hoạt động thế nào;",
            "thấy độ khó đổi rủi ro ra sao;",
            "quen nút Cash Out;",
            "thử phong cách chơi của bạn;",
            "nhận ra rủi ro tăng nhanh thế nào;",
            "chơi mà không lo số dư thật.",
        ],
        "why_after": [
            "Cũng là chỗ tốt để xem hành vi của bạn. Một số cash out sớm. Một số cứ nhấn thêm một bước. Một số vào thẳng Hardcore chỉ để xem. Ở demo tất cả đều ổn vì số dư là ảo.",
            "Chơi tiền thật khác. Ở đó cùng quyết định nặng hơn. Trước khi mạo hiểm, nên dành thời gian ở demo và hiểu game đúng cách.",
        ],
        "mobile_h2": "Ice Fish Demo trên di động",
        "mobile_paras": [
            "Demo Ice Fish chạy tốt trên điện thoại. Game đơn giản, không cần màn hình lớn. Mở là thấy gà, đường, số cược và nút chính — đủ rồi.",
            "Trên mobile màn hình dọc nên game trông hơi khác. Đường không rộng như desktop. Một số phần tử gần nhau hơn, menu độ khó thường gói trong một nút.",
            "Các nút vẫn dễ dùng. Đặt cược ảo, nhấn Play, cho gà tiến và cash out khi không muốn rủi ro bước tiếp.",
            "Tốc độ tải phụ thuộc điện thoại và mạng. Máy mới mở demo nhanh hơn. Điện thoại cũ hoặc sóng yếu có thể chậm chút. Nhưng không phải game phải bấm hoảng mỗi giây — sau bước an toàn bạn có thể dừng suy nghĩ.",
            "Bạn có thể chơi demo thẳng từ trình duyệt. Không bắt buộc app, nhưng muốn truy cập nhanh hơn dùng shortcut App trên site. Nó đặt icon Ice Fish Demo lên màn hình chính, lần sau không phải tìm trang.",
        ],
        "download_h2": "Có thể tải Ice Fish Demo không?",
        "download_paras": [
            "Bạn không thực sự cần tải Ice Fish Demo để chơi. Demo chạy qua web — mở từ trình duyệt và chơi ngay bằng số dư ảo.",
            "Trên site chúng tôi có thể có shortcut kiểu app hoặc PWA. Trên điện thoại trông và cảm giác như app, nhưng demo vẫn chạy qua website. Điểm chính là tiện: chạm icon và mở Ice Fish nhanh hơn.",
            "Cẩn thận file APK ngẫu nhiên từ site khác. Trang nào offer «APK Ice Fish đặc biệt», «bản hack» hay «app dự đoán» thì nên bỏ qua. File có thể giả, đôi khi chỉ để thu thập dữ liệu.",
            "Với chơi demo bình thường, bản trình duyệt là đủ. Mở trang, khởi chạy game và thử Ice Fish mà không cài gì rủi ro.",
        ],
        "trouble_h2": "Nếu Ice Fish Demo không khởi động",
        "trouble_paras": [
            "Đôi khi demo chỉ không chạy. Không có bí ẩn lớn — phần lớn là trình duyệt hoặc internet, không phải game.",
            "Bắt đầu đơn giản: làm mới trang. Loader kẹt thì đóng tab mở lại. Vẫn không được? Thử trình duyệt khác. Chrome, Safari, Firefox, Samsung Internet — thường một cái chạy tốt hơn.",
            "Nút Play hiện nhưng không làm gì thì đợi chút. Nút có thể hiện trước khi game tải xong. Hay gặp trên mobile data hoặc điện thoại cũ.",
            "Điện thoại đơ cũng thường đơn giản. Kiểm tra gì đang quá tải hệ thống máy.",
            "Không thấy số dư ảo trên màn hình? Nhìn kỹ góc trên bên phải — số dư phải hiển thị ở đó.",
            "Trình chặn quảng cáo cũng có thể làm hỏng trang. Một số chặn nhầm script game. Tắt chặn cho trang này hoặc thử chế độ riêng tư. Và kiểm tra internet. Demo không nặng nhưng sóng yếu vẫn có thể treo trước vòng đầu.",
        ],
        "safety_h2": "An toàn demo và chơi công bằng",
        "safety_paras": [
            "Ice Fish Demo được tạo cho một việc — để bạn thử game trước khi dùng tiền thật. Mở, chơi số dư ảo, thử nút và xem vòng hoạt động. Demo chỉ nên làm vậy.",
            "Bạn không nên phải nhập số thẻ, CVV, SMS, hộ chiếu hay dữ liệu thanh toán riêng chỉ để chơi bản miễn phí. Site yêu cầu nạp trước khi mở «chế độ demo» là dấu xấu.",
            "Demo trên site chúng tôi để luyện tập và làm quen. Bạn có thể xem cơ chế, thử độ khó và hiểu hệ số nhân đổi sau mỗi bước an toàn. Không cần trả tiền thật.",
            "Cẩn thận trang dùng tên Ice Fish nhưng hứa «thắng chắc», «dự đoán bí mật» hay «demo đặc biệt trả tiền thật». Demo bình thường không hoạt động vậy — dùng credit ảo và mọi kết quả ở trong demo.",
            "Quy tắc đơn giản: nếu thật sự là demo miễn phí thì không nên xin tiền.",
        ],
        "faq_h2": "FAQ — Ice Fish Demo",
        "faq": [
            ("Tôi có thể chơi Ice Fish Demo miễn phí không?", "Có. Demo trên site chúng tôi miễn phí và bạn không mạo hiểm tiền thật."),
            ("Tôi có cần đăng ký không?", "Không. Không cần đăng ký chỉ để thử game."),
            ("Tôi có thể thắng tiền thật trong demo không?", "Không. Thắng demo ở trong demo. Bạn có thể tăng số dư ảo, thua, khởi động lại — nhưng không rút được gì."),
            ("Chế độ demo có giống game thật không?", "Gameplay giống nhau. Cùng gà, đường, độ khó, ý tưởng cash out. Khác ở số dư: demo là ảo, tiền thật là tiền của bạn."),
            ("Ice Fish Demo có chạy trên mobile không?", "App được thiết kế đặc biệt với phiên bản mobile."),
            ("Tôi có phải tải gì không?", "Không. Demo chơi trên trình duyệt. Muốn nhanh hơn dùng shortcut trên site, nhưng không bắt buộc."),
            ("Có thể dự đoán bước tiếp theo không?", "Không. Bạn không biết trước điều gì xảy ra ở nước tiếp. App hoặc site hứa «dự đoán Ice Fish» nên cẩn thận."),
            ("Có chiến lược cho chế độ demo không?", "Bạn có thể thử phong cách khác nhau: cash out sớm, chỉ Easy hoặc độ khó cao hơn để cảm rủi ro. Nhưng demo không tiết lộ hệ thống chắc chắn."),
            ("Đây có phải game Ice Fish chính thức không?", "Ice Fish do InOut Games phát triển. Demo trên site chúng tôi để luyện tập. Site chúng tôi không phải site chính thức của InOut Games."),
        ],
        "titles": {
            "start_steps_title": "Bắt đầu trong bốn bước",
            "step1_h": "1. Mở và chọn độ khó",
            "step1_p": "Khởi chạy demo trên trình duyệt hoặc app và chọn chế độ để bắt đầu.",
            "step2_h": "2. Đặt cược",
            "step2_p": "Đặt cược ảo để thử hệ số nhân trong vòng.",
            "step3_h": "3. Cash Out",
            "step3_p": "Cash out sau bước an toàn và giữ hệ số nhân hiện tại.",
        },
        "alts": {
            "gameplay": "Gameplay demo Ice Fish với hệ số nhân trên màn hình",
            "app": "Truy cập demo Ice Fish qua app hoặc trình duyệt",
            "mobile": "Ice Fish Demo trên mobile ở chế độ dọc",
            "interface": "Giao diện demo Ice Fish trên nhiều thiết bị",
            "step1": "Bước 1 — mở demo Ice Fish và chọn độ khó",
            "step2": "Bước 2 — đặt cược ảo trong Ice Fish Demo",
            "step3": "Bước 3 — Cash Out trong Ice Fish Demo sau bước an toàn",
        },
    }


def _ro() -> dict:
    return {
        "intro_h2": "Ice Fish Demo — joacă gratuit fără risc",
        "intro_paras": [
            "Ice Fish a devenit popular pentru că la început nu te obligă să gândești prea mult. Îl deschizi și înțelegi ideea aproape imediat. Fără haos de slot clasic, fără role care se învârt, fără simboluri confuze. Arată ca un arcade mic, dar fiecare pas următor poartă totuși risc.",
            "De aceea Ice Fish funcționează atât de bine. Se simte mai degrabă ca un joc arcade decât ca un titlu clasic de casino. Vezi găina, drumul, mașinile, iar decizia principală rămâne clară: păstrezi rezultatul actual sau încerci încă un pas.",
            "InOut Games a reușit să adune ce atrage tipuri diferite de jucători. Jocul pare ușor și amuzant, cu reguli simple. Nu durează mult să-l înțelegi, dar odată ce ai început îți ține ușor atenția.",
            "Versiunea gratuită de pe site-ul nostru e făcută exact pentru asta — să încerci Ice Fish singur. Poți deschide demo-ul, juca cu sold virtual, testa mecanica și te bucura fără să cheltui un cent.",
            "E un mod bun să înțelegi de ce jocul a devenit atât de popular înainte să te apropii de jocul pe bani reali.",
        ],
        "what_h2": "Ce este modul demo Ice Fish?",
        "what_paras": [
            "Ice Fish Demo este versiunea gratuită a jocului. Joci cu sold virtual, nu cu bani reali. Asta e ideea principală. Poți deschide jocul, pune un pariu de test, alege dificultatea și vedea cum găina avansează pe drum fără teama să-ți pierzi banii. Dacă greșești, nu se întâmplă nimic grav — pierzi doar credite demo și reîncepi.",
            "Demo-ul nu e alt joc. Funcționează aproape la fel ca versiunea reală. Găina merge pas cu pas, multiplicatorul crește după fiecare pas sigur și tot trebuie să decizi când te oprești. Cash out acum sau încă un pas — asta e toată tensiunea Ice Fish.",
            "Pentru începători, e cel mai bun start. Nu ai nevoie de depozit, înregistrare sau griji despre sold. Poți verifica butonul Cash Out și vedea cât de repede o rundă poate trece de la sigur la pierdut.",
            "Și asta contează: modul demo e util, dar nu te face mai bun decât jocul. Câteva runde bune cu credite virtuale nu înseamnă că ai găsit un sistem câștigător. În demo oamenii joacă de obicei mai liber: riscă mai mult, așteaptă mai mult și nu le pasă mult de pierdere.",
            "Banii reali schimbă totul. Același pas se simte diferit când soldul tău e în joc. Folosește Ice Fish Demo pentru ce face bine — învață jocul, testează butoanele și înțelege riscul înainte să te gândești la pariuri reale.",
        ],
        "start_h2": "Cum pornești Ice Fish Demo",
        "start_steps": [
            "Pornirea demo-ului Ice Fish e destul de simplă. Descarcă și instalează aplicația dacă vrei și deschide-o după instalare. Poți juca și direct în browser pe pagina site-ului nostru.",
            "Înainte de start, alege nivelul de dificultate. Începe cu Easy dacă vrei doar să înțelegi jocul, sau treci la moduri mai grele mai târziu ca să vezi cum se schimbă riscul.",
            "Apoi setează pariul virtual ca să testezi sume diferite și să vezi cum funcționează multiplicatorul în rundă.",
            "Avansează găina pas cu pas. Dacă pasul e sigur, multiplicatorul crește. Oricând poți apăsa Cash Out și lua câștigul virtual curent.",
        ],
        "start_summary": "Tot procesul e simplu: deschide demo-ul, alege modul, setează pariul, avansează și oprește-te când simți că riscul e suficient.",
        "vs_h2": "Demo vs joc pe bani reali",
        "vs_paras": [
            "Jocul în sine nu se schimbă cu adevărat. Aceeași găină, același drum, aceleași butoane, aceeași idee. Diferența e soldul.",
            "În demo joci cu credite virtuale. Le poți pierde, reporni, încerca altă dificultate — banii tăi nu sunt afectați. De aceea oamenii joacă demo mai liber: pași în plus, risc mai mare, apasă «Go» doar ca să vadă ce se întâmplă.",
            "Cu bani reali senzația e alta. Chiar și aceeași decizie mică poate părea mai grea. Te gândești la pariu, pierdere, șansa de recuperare. Unii fac cash out prea devreme din nervi. Alții merg prea departe pentru un rezultat mai mare.",
            "Da, diferența tehnică e simplă: sold virtual în demo, fonduri reale în modul pe bani reali. Dar emoțional nu e același joc. Demo-ul e pentru învățare. Jocul pe bani reali adaugă presiune, iar presiunea schimbă deciziile repede.",
        ],
        "why_h2": "De ce să începi cu demo-ul?",
        "why_before": [
            "Demo-ul e cel mai sigur mod să înțelegi Ice Fish înainte de bani reali. Poți greși acolo — asta e scopul.",
            "În modul gratuit testezi mecanica de bază fără presiune. Alege dificultatea, pune un pariu virtual, avansează găina și simte cum merge runda. După câteva încercări jocul devine mult mai clar.",
        ],
        "why_bullets_intro": "Demo-ul ajută la câteva lucruri:",
        "why_bullets": [
            "înțelegi cum funcționează jocul;",
            "vezi cum dificultatea schimbă riscul;",
            "te obișnuiești cu butonul Cash Out;",
            "poți testa stilul tău de joc;",
            "observi cât de repede crește riscul;",
            "joci fără griji despre soldul real.",
        ],
        "why_after": [
            "E și un loc bun să-ți verifici comportamentul. Unii fac cash out devreme. Alții apasă încă un pas. Alții merg direct la Hardcore doar ca să vadă. În demo totul e în regulă, pentru că soldul e virtual.",
            "Jocul pe bani reali e diferit. Acolo aceeași decizie se simte mai grea. Înainte să riști ceva, are sens să petreci timp în demo și să înțelegi jocul cum trebuie.",
        ],
        "mobile_h2": "Ice Fish Demo pe mobil",
        "mobile_paras": [
            "Demo-ul Ice Fish merge bine pe telefon. Jocul e simplu, nu are nevoie de ecran uriaș. Îl deschizi, vezi găina, drumul, suma pariului și butoanele principale — e suficient.",
            "Pe mobil ecranul e vertical, deci jocul arată puțin diferit. Drumul nu e la fel de lat ca pe desktop. Unele elemente sunt mai apropiate, iar meniul de dificultate e de obicei într-un singur buton.",
            "Butoanele sunt tot ușor de folosit. Setează un pariu virtual, apasă Play, avansează găina și fă cash out când nu vrei să riști pasul următor.",
            "Încărcarea depinde de telefon și internet. Un dispozitiv nou deschide demo-ul mai repede. Un telefon vechi sau semnal slab îl pot încetini puțin. Dar nu e un joc în care trebuie să apeși în panică în fiecare secundă — după un pas sigur poți opri și gândi.",
            "Poți juca demo-ul direct din browser. Aplicația nu e obligatorie, dar pentru acces mai rapid folosește scurtătura App de pe site. Pune iconița Ice Fish Demo pe ecranul principal, ca data viitoare să nu cauți pagina.",
        ],
        "download_h2": "Poți descărca Ice Fish Demo?",
        "download_paras": [
            "Nu ai nevoie cu adevărat să descarci Ice Fish Demo ca să joci. Demo-ul merge prin web — îl deschizi din browser și joci imediat cu sold virtual.",
            "Pe site-ul nostru putem oferi și acces rapid printr-o scurtătură tip app sau PWA. Pe telefon arată și se simte ca o aplicație, dar demo-ul tot rulează prin site. Ideea e confortul: atingi iconița și deschizi Ice Fish mai repede.",
            "Ai grijă la fișiere APK aleatoare de pe alte site-uri. Dacă o pagină oferă «APK special Ice Fish», «versiune hack» sau «app predictor», mai bine sari peste. Fișierele pot fi false și uneori sunt făcute doar să colecteze date.",
            "Pentru joc demo normal, versiunea din browser e suficientă. Deschide pagina, lansează jocul și testează Ice Fish fără instalări riscante.",
        ],
        "trouble_h2": "Dacă Ice Fish Demo nu pornește",
        "trouble_paras": [
            "Uneori demo-ul pur și simplu nu pornește. Nu e mare mister — de cele mai multe ori e browserul sau internetul, nu jocul.",
            "Începe simplu: reîmprospătează pagina. Dacă încărcarea e blocată, închide tab-ul și deschide din nou. Tot nimic? Încearcă alt browser. Chrome, Safari, Firefox, Samsung Internet — de obicei unul merge mai bine.",
            "Dacă butonul Play e vizibil dar nu face nimic, așteaptă puțin. Butonul poate apărea înainte ca jocul să se încarce complet. Se întâmplă mai des pe date mobile sau telefoane vechi.",
            "Înghețările telefonului sunt de obicei simple. Verifică ce poate suprasolicita sistemul dispozitivului.",
            "Fără sold virtual pe ecran? Uită-te atent în colțul din dreapta sus — soldul ar trebui afișat acolo.",
            "Blocatorii de reclame pot strica pagina. Unii blochează din greșeală scripturile jocului. Dezactivează blocarea pentru această pagină sau încearcă modul privat. Și verifică internetul. Demo-ul nu e greu, dar semnal slab poate lăsa jocul blocat înainte de prima rundă.",
        ],
        "safety_h2": "Siguranța demo și joc corect",
        "safety_paras": [
            "Ice Fish Demo e făcut pentru un lucru — să încerci jocul înainte de bani reali. Îl deschizi, joci cu sold virtual, testezi butoanele și vezi cum merge runda. Asta ar trebui să facă un demo.",
            "Nu ar trebui să introduci număr de card, CVV, SMS, pașaport sau date de plată private doar pentru versiunea gratuită. Dacă un site cere depozit înainte de «mod demo», e deja semn rău.",
            "Demo-ul de pe site-ul nostru e pentru practică și familiarizare. Poți verifica mecanica, încerca niveluri de dificultate și înțelege cum se schimbă multiplicatorul după fiecare pas sigur. Nu e nevoie de plată reală.",
            "Ai grijă la pagini care folosesc numele Ice Fish dar promit «câștiguri garantate», «predicție secretă» sau «demo special cu plăți reale». Un demo normal nu funcționează așa — folosește credite virtuale și orice rezultat rămâne în demo.",
            "Regulă simplă: dacă e cu adevărat demo gratuit, nu ar trebui să ceară bani.",
        ],
        "faq_h2": "FAQ — Ice Fish Demo",
        "faq": [
            ("Pot juca Ice Fish Demo gratuit?", "Da. Demo-ul de pe site-ul nostru e gratuit și nu riști bani reali."),
            ("Trebuie să mă înregistrez?", "Nu. Nu e nevoie de înregistrare doar ca să testezi jocul."),
            ("Pot câștiga bani reali în demo?", "Nu. Câștigurile din demo rămân în demo. Poți crește soldul virtual, pierde, reporni — dar nu poți retrage nimic."),
            ("Modul demo e la fel ca jocul real?", "Gameplay-ul e același. Aceeași găină, drum, niveluri de dificultate, aceeași idee de cash out. Diferența e soldul: virtual în demo, banii tăi în modul pe bani reali."),
            ("Ice Fish Demo merge pe mobil?", "Aplicația a fost concepută special cu versiunea mobilă în minte."),
            ("Trebuie să descarc ceva?", "Nu. Demo-ul se joacă în browser. Pentru acces mai rapid poți folosi scurtătura de pe site, dar nu e obligatoriu."),
            ("Se poate prezice pasul următor?", "Nu. Nu poți ști dinainte ce se întâmplă la mutarea următoare. Orice app sau site care promite «predicții Ice Fish» merită tratat cu prudență."),
            ("Există strategie pentru modul demo?", "Poți testa stiluri diferite: cash out devreme, doar Easy sau dificultate mai mare ca să simți riscul. Dar demo-ul nu dezvăluie un sistem garantat."),
            ("Acesta e jocul oficial Ice Fish?", "Ice Fish e dezvoltat de InOut Games. Demo-ul de pe site-ul nostru e pentru practică. Site-ul nostru nu e site-ul oficial InOut Games."),
        ],
        "titles": {
            "start_steps_title": "Cum să începi în patru pași",
            "step1_h": "1. Deschide și alege dificultatea",
            "step1_p": "Pornește demo-ul în browser sau app și alege un mod pentru start.",
            "step2_h": "2. Setează pariul",
            "step2_p": "Definește un pariu virtual ca să testezi multiplicatorul în rundă.",
            "step3_h": "3. Cash Out",
            "step3_p": "Fă cash out după un pas sigur și păstrează multiplicatorul actual.",
        },
        "alts": {
            "gameplay": "Gameplay demo Ice Fish cu multiplicator pe ecran",
            "app": "Acces demo Ice Fish prin app sau browser",
            "mobile": "Ice Fish Demo pe mobil în mod portret",
            "interface": "Interfața demo Ice Fish pe mai multe dispozitive",
            "step1": "Pasul 1 — deschide demo Ice Fish și alege dificultatea",
            "step2": "Pasul 2 — definește pariul virtual în Ice Fish Demo",
            "step3": "Pasul 3 — Cash Out în Ice Fish Demo după un pas sigur",
        },
    }


def _py_str(s: str) -> str:
    return repr(s)


def _format_dict(d: dict, indent: int = 4) -> str:
    sp = " " * indent
    lines = ["{"]
    order = [
        "intro_h2", "intro_paras", "what_h2", "what_paras", "start_h2", "start_steps",
        "start_summary", "vs_h2", "vs_paras", "why_h2", "why_before", "why_bullets_intro",
        "why_bullets", "why_after", "mobile_h2", "mobile_paras", "download_h2",
        "download_paras", "trouble_h2", "trouble_paras", "safety_h2", "safety_paras",
        "faq_h2", "faq", "titles", "alts",
    ]
    for key in order:
        val = d[key]
        if isinstance(val, str):
            lines.append(f'{sp}"{key}": {_py_str(val)},')
        elif isinstance(val, list) and val and isinstance(val[0], tuple):
            lines.append(f'{sp}"{key}": [')
            for q, a in val:
                lines.append(f"            ({_py_str(q)}, {_py_str(a)}),")
            lines.append(f"{sp}],")
        elif isinstance(val, list):
            lines.append(f'{sp}"{key}": [')
            for item in val:
                lines.append(f"            {_py_str(item)},")
            lines.append(f"{sp}],")
        elif isinstance(val, dict):
            lines.append(f'{sp}"{key}": {{')
            for sk, sv in val.items():
                lines.append(f'{sp}    "{sk}": {_py_str(sv)},')
            lines.append(f"{sp}}},")
    lines.append("    }")
    return "\n".join(lines)


def main() -> None:
    locales: list[tuple[str, dict]] = []
    for code, path, fn in PIECES:
        locales.append((code, _load(fn, path)))
    for fn in (_az, _vi, _ro):
        locales.append((fn.__name__[1:], fn()))

    parts = [
        '# -*- coding: utf-8 -*-',
        '"""Full localized body copy for Ice Fish demo page (14 locales)."""',
        "",
        "from __future__ import annotations",
        "",
        "",
        "def get_all_full_locales() -> dict[str, dict]:",
        "    return {",
    ]
    for code, _ in locales:
        parts.append(f'        "{code}": _{code}(),')
    parts.append("    }")
    parts.append("")

    for code, data in locales:
        parts.append(f"")
        parts.append(f"def _{code}() -> dict:")
        parts.append(f"    return {_format_dict(data)}")
        parts.append("")

    OUT.write_text("\n".join(parts) + "\n", encoding="utf-8")
    print(f"Wrote {OUT} ({OUT.stat().st_size} bytes)")


if __name__ == "__main__":
    main()
