# -*- coding: utf-8 -*-
"""Natural Polish, Ukrainian, and Romanian body copy for Chicken Road demo page."""

from __future__ import annotations


def get_slavic_locales() -> dict[str, dict]:
    return {
        "pl": _pl(),
        "ua": _ua(),
        "ro": _ro(),
    }


def _pl() -> dict:
    return {
        "intro_h2": "Chicken Road Demo — graj za darmo bez ryzyka",
        "intro_paras": [
            "Chicken Road stała się popularna, bo na początku nie zmusza gracza do długiego zastanawiania się. Otwierasz grę i niemal od razu rozumiesz, o co chodzi. Bez klasycznego chaosu slotów, bez kręcących się bębnów i mylących symboli. Wygląda jak mała gra zręcznościowa, ale każdy kolejny krok nadal niesie ryzyko.",
            "Dlatego Chicken Road tak dobrze działa. Bardziej przypomina grę arcade niż klasyczny tytuł kasynowy. Widzisz kurczaka, drogę, samochody — a główna decyzja jest zawsze jasna: zostać przy obecnym wyniku czy spróbować jeszcze jednego kroku.",
            "InOut Games połączyli tu to, co przyciąga różne typy graczy. Gra wydaje się lekka i zabawna, zasady są proste. Szybko się w nią wchodzi, a po starcie łatwo utrzymać uwagę.",
            "Darmowa wersja na naszej stronie służy właśnie temu — żebyś sam wypróbował Chicken Road. Otwórz demo, graj na wirtualnym saldzie, testuj mechanikę i ciesz się grą bez wydawania ani grosza.",
            "To dobry sposób, by zrozumieć, dlaczego gra stała się tak popularna, zanim przejdziesz do gry na prawdziwe pieniądze.",
        ],
        "what_h2": "Czym jest tryb demo Chicken Road?",
        "what_paras": [
            "Chicken Road Demo to darmowa wersja gry. Grasz na wirtualnym saldzie, a nie na prawdziwe pieniądze. O to chodzi. Możesz otworzyć grę, postawić zakład testowy, wybrać poziom trudności i obserwować, jak kurczak idzie po drodze, bez obawy o utratę własnych środków. Pomyłka? Nic strasznego — tracisz tylko kredyty demo i zaczynasz od nowa.",
            "Demo to nie inna gra. Działa prawie tak samo jak pełna wersja. Kurczak nadal idzie krok po kroku, mnożnik rośnie po każdym bezpiecznym ruchu, a Ty wciąż musisz zdecydować, kiedy się zatrzymać. Wypłacić teraz czy zaryzykować jeszcze jeden krok — w tym całe napięcie Chicken Road.",
            "Dla początkujących to najlepszy start. Nie trzeba wpłacać depozytu, rejestrować się ani martwić o saldo. Możesz sprawdzić, jak działa przycisk Cash Out i zobaczyć, jak szybko runda może przejść od bezpiecznej do przegranej.",
            "I to ważne: demo jest przydatne, ale nie czyni Cię lepszym od samej gry. Kilka udanych rund na wirtualnych kredytach nie oznacza, że znalazłeś wygrywający system. W demo ludzie zwykle grają swobodniej: ryzykują więcej, czekają dłużej i mniej przejmują się przegraną.",
            "Prawdziwe pieniądze zmieniają wszystko. Ten sam krok odczuwasz inaczej, gdy na szali jest Twoje własne saldo. Używaj Chicken Road Demo tak, jak do tego służy — poznaj grę, przetestuj przyciski i zrozum ryzyko, zanim pomyślisz o prawdziwych zakładach.",
        ],
        "start_h2": "Jak uruchomić demo Chicken Road",
        "start_steps": [
            "Uruchomienie demo Chicken Road jest proste. Jeśli chcesz, pobierz i zainstaluj aplikację — po instalacji ją otwórz. Możesz też grać od razu w przeglądarce na stronie naszego serwisu.",
            "Przed startem wybierz poziom trudności. Zacznij od Easy, jeśli chcesz po prostu zrozumieć grę, albo przełącz się na wyższe tryby, gdy zechcesz zobaczyć, jak zmienia się ryzyko.",
            "Następnie ustaw wirtualny zakład, by przetestować różne kwoty i zobaczyć, jak działa mnożnik w rundzie.",
            "Prowadź kurczaka naprzód krok po kroku. Po bezpiecznym kroku mnożnik rośnie. W każdej chwili możesz nacisnąć Cash Out i odebrać bieżącą wirtualną wygraną.",
        ],
        "start_summary": "Cały proces jest prosty: otwórz demo, wybierz tryb, ustaw zakład, idź naprzód i zatrzymaj się, gdy ryzyko wydaje Ci się wystarczające.",
        "vs_h2": "Demo a gra na prawdziwe pieniądze",
        "vs_paras": [
            "Sama gra prawie się nie zmienia. Ten sam kurczak, ta sama droga, te same przyciski, ta sama idea. Różnica jest w saldzie.",
            "W demo grasz na wirtualne kredyty. Możesz je stracić, zacząć od nowa, spróbować innej trudności — a Twoje pieniądze pozostają nietknięte. Dlatego w demo zwykle gra się swobodniej: robi się dodatkowe kroki, testuje wyższe ryzyko i klika „Go” po prostu po to, by zobaczyć, co się stanie.",
            "Na prawdziwe pieniądze odczucia są inne. Nawet ta sama decyzja wydaje się cięższa. Myślisz o zakładzie, o przegranej, o szansie na odrobienie. Niektórzy wypłacają zbyt wcześnie ze względu na nerwy. Inni idą za daleko w pogoni za większym wynikiem.",
            "Technicznie różnica jest prosta: wirtualne saldo w demo, prawdziwe środki w trybie na pieniądze. Emocjonalnie to jednak nie ta sama gra. Demo służy do nauki. Gra na prawdziwe pieniądze dodaje presji, a presja szybko zmienia decyzje.",
        ],
        "why_h2": "Dlaczego zacząć od demo?",
        "why_before": [
            "Demo to najbezpieczniejszy sposób, by zrozumieć Chicken Road, zanim użyjesz prawdziwych pieniędzy. Można się tu pomylić — o to właśnie chodzi.",
            "W darmowym trybie testujesz podstawową mechanikę bez presji. Wybierz trudność, postaw wirtualny zakład, prowadź kurczaka naprzód i poczuj, jak przebiega runda. Po kilku próbach gra staje się znacznie jaśniejsza.",
        ],
        "why_bullets_intro": "Demo pomaga w kilku kwestiach:",
        "why_bullets": [
            "zrozumieć, jak działa gra;",
            "zobaczyć, jak trudność wpływa na ryzyko;",
            "przyzwyczaić się do przycisku Cash Out;",
            "sprawdzić swój styl gry;",
            "zauważyć, jak szybko rośnie ryzyko;",
            "grać bez martwienia się o prawdziwe saldo.",
        ],
        "why_after": [
            "To też dobre miejsce, by sprawdzić swoje zachowanie. Ktoś wypłaca wcześniej. Ktoś naciska „jeszcze jeden krok”. Ktoś od razu idzie w Hardcore, żeby zobaczyć, co będzie. W demo to w porządku, bo saldo jest wirtualne.",
            "Gra na prawdziwe pieniądze jest inna. Tam ta sama decyzja waży więcej. Zanim cokolwiek zaryzykujesz, warto spędzić czas w demo i dobrze poznać grę.",
        ],
        "mobile_h2": "Demo Chicken Road na telefonie",
        "mobile_paras": [
            "Demo Chicken Road świetnie działa na telefonie. Gra jest prosta i nie wymaga ogromnego ekranu. Otwierasz ją, widzisz kurczaka, drogę, kwotę zakładu i główne przyciski — to wystarczy.",
            "Na telefonie ekran jest pionowy, więc gra wygląda nieco inaczej. Droga nie jest tak szeroka jak na komputerze. Niektóre elementy są bliżej siebie, a menu trudności zwykle chowa się w jednym przycisku.",
            "Przyciski nadal są wygodne. Ustaw wirtualny zakład, naciśnij Play, prowadź kurczaka naprzód i naciskaj Cash Out, gdy nie chcesz ryzykować kolejnego kroku. Nic skomplikowanego.",
            "Szybkość ładowania zależy od telefonu i internetu. Nowsze urządzenie otworzy demo szybciej. Starszy telefon lub słaby sygnał mogą nieco spowolnić start. To jednak nie gra, w której trzeba klikać w panice co sekundę — po bezpiecznym kroku możesz się zatrzymać i pomyśleć.",
            "Możesz grać w demo prosto z przeglądarki. Aplikacja nie jest konieczna, ale dla szybszego dostępu użyj skrótu z naszej strony. Doda ikonę Chicken Road Demo na ekran główny, żeby następnym razem nie szukać strony od nowa.",
        ],
        "download_h2": "Czy trzeba pobierać demo Chicken Road?",
        "download_paras": [
            "Do gry w demo Chicken Road pobieranie nie jest konieczne. Demo działa w przeglądarce — otwierasz je w sieci i od razu grasz na wirtualnym saldzie.",
            "Na naszej stronie może być też szybki dostęp przez skrót aplikacji lub PWA. Na telefonie wygląda i działa jak aplikacja, ale demo nadal działa przez stronę. Chodzi o wygodę: dotykasz ikony i szybciej otwierasz Chicken Road, bez szukania strony.",
            "Uważaj na losowe pliki APK z innych serwisów. Jeśli jakaś strona oferuje „specjalny APK Chicken Road”, „zhakowaną wersję” lub „aplikację-predictor”, lepiej ją pominąć. Takie pliki mogą być podróbką i czasem służą tylko do zbierania danych albo prowadzą na niebezpieczne strony.",
            "Do zwykłej gry w demo wystarczy wersja przeglądarkowa. Otwórz stronę, uruchom grę i przetestuj Chicken Road bez ryzykownej instalacji.",
        ],
        "trouble_h2": "Jeśli demo Chicken Road nie uruchamia się",
        "trouble_paras": [
            "Czasem demo po prostu nie startuje. Bez wielkiej tajemnicy — najczęściej winna jest przeglądarka lub internet, a nie sama gra.",
            "Zacznij od prostego: odśwież stronę. Jeśli wskaźnik ładowania się zawiesił, zamknij kartę i otwórz ponownie. Nadal nic? Spróbuj innej przeglądarki. Chrome, Safari, Firefox, Samsung Internet — zwykle któraś działa lepiej.",
            "Jeśli przycisk Play jest widoczny, ale nic się nie dzieje, poczekaj chwilę. Przycisk może pojawić się, zanim gra w pełni się załaduje. To częstsze na mobilnym internecie lub starszych telefonach.",
            "Zawieszanie telefonu też zwykle da się rozwiązać prosto. Sprawdź, co może obciążać system urządzenia.",
            "Nie widzisz wirtualnego salda na ekranie? Przyjrzyj się uważnie prawemu górnemu rogowi — tam powinno być wyświetlone saldo.",
            "Blokery reklam też mogą psuć stronę. Niektóre przez pomyłkę blokują skrypty gry. Wyłącz blokadę dla tej strony albo spróbuj trybu prywatnego. I sprawdź internet: demo Chicken Road nie jest ciężkie, ale słaby sygnał może zostawić grę na ładowaniu przed pierwszą rundą.",
        ],
        "safety_h2": "Bezpieczeństwo demo i uczciwa gra",
        "safety_paras": [
            "Chicken Road Demo powstało po to, by wypróbować grę, zanim użyjesz prawdziwych pieniędzy. Otwierasz ją, grasz na wirtualnym saldzie, testujesz przyciski i patrzysz, jak działa runda. Tylko tyle powinno robić demo.",
            "Do darmowej wersji nie powinno się wymagać numeru karty, CVV, potwierdzenia SMS, danych paszportowych ani żadnych informacji płatniczych. Jeśli serwis prosi o depozyt przed otwarciem „trybu demo”, to już zły znak.",
            "Demo na naszej stronie służy do ćwiczeń i podstawowego poznania gry. Możesz sprawdzić mechanikę, wypróbować różne poziomy trudności i zrozumieć, jak zmienia się mnożnik po każdym bezpiecznym kroku. Prawdziwa płatność nie jest potrzebna.",
            "Uważaj na strony, które używają nazwy Chicken Road, ale obiecują „gwarantowane wygrane”, „tajne przewidywanie” lub „specjalne demo z prawdziwymi wypłatami”. Zwykłe demo tak nie działa. Korzysta z wirtualnych kredytów, a każdy wynik w demo zostaje w demo.",
            "Prosta zasada: jeśli to naprawdę darmowe demo, nie powinno prosić o pieniądze.",
        ],
        "faq_h2": "FAQ — demo Chicken Road",
        "faq": [
            (
                "Czy mogę grać w Chicken Road Demo za darmo?",
                "Tak. Demo na naszej stronie jest darmowe i nie ryzykujesz prawdziwymi pieniędzmi.",
            ),
            (
                "Czy potrzebna jest rejestracja?",
                "Nie. Do przetestowania gry rejestracja nie jest wymagana.",
            ),
            (
                "Czy w demo można wygrać prawdziwe pieniądze?",
                "Nie. Wygrane w demo zostają w demo. Możesz zwiększyć wirtualne saldo, je stracić, zacząć od nowa — ale niczego nie wypłacisz.",
            ),
            (
                "Czy tryb demo jest taki sam jak gra na pieniądze?",
                "Rozgrywka jest taka sama. Ten sam kurczak, ta sama droga, te same poziomy trudności, ta sama logika wypłaty. Różnica jest w saldzie: w demo jest wirtualne, w trybie na pieniądze — Twoje własne.",
            ),
            (
                "Czy Chicken Road Demo działa na telefonie?",
                "Tak. Gra od początku była projektowana z myślą o wersji mobilnej.",
            ),
            (
                "Czy trzeba coś pobierać?",
                "Nie. Demo można grać w przeglądarce. Dla szybszego dostępu możesz użyć skrótu z naszej strony, ale to nie jest obowiązkowe.",
            ),
            (
                "Czy można przewidzieć następny krok?",
                "Nie. Z góry nie wiadomo, co stanie się przy następnym ruchu. Do każdej aplikacji lub strony z „przewidywaniami Chicken Road” warto podchodzić ostrożnie.",
            ),
            (
                "Czy jest strategia na tryb demo?",
                "W demo możesz testować różne style: wcześniejsza wypłata, gra tylko na Easy albo wyższa trudność, by poczuć ryzyko. Demo nie ujawnia jednak gwarantowanego systemu.",
            ),
            (
                "Czy to oficjalna gra Chicken Road?",
                "Chicken Road została opracowana przez InOut Games. Demo na naszej stronie służy do ćwiczeń i poznania gry. Nasz serwis nie jest oficjalną stroną InOut Games.",
            ),
        ],
        "titles": {
            "start_steps_title": "Jak zacząć: cztery kroki",
            "step1_h": "1. Otwórz",
            "step1_p": "Uruchom demo w przeglądarce lub przez skrót z naszej strony.",
            "step2_h": "2. Ustaw",
            "step2_p": "Wybierz trudność i ustaw wirtualny zakład przed rundą.",
            "step3_h": "3. Cash Out",
            "step3_p": "Prowadź kurczaka naprzód i odbieraj mnożnik, gdy ryzyko wydaje Ci się wystarczające.",
        },
        "alts": {
            "gameplay": "Rozgrywka demo Chicken Road z mnożnikiem na ekranie",
            "app": "Demo Chicken Road na telefonie i komputerze",
            "mobile": "Mobilny interfejs Chicken Road w trybie pionowym",
            "interface": "Interfejs Chicken Road na różnych urządzeniach",
            "step1": "Krok 1: otwórz demo Chicken Road",
            "step2": "Krok 2: wybierz trudność i zakład",
            "step3": "Krok 3: wypłata w demo Chicken Road",
        },
    }


def _ua() -> dict:
    return {
        "intro_h2": "Chicken Road Demo — грайте безкоштовно без ризику",
        "intro_paras": [
            "Chicken Road стала популярною, бо на початку не змушує гравця довго думати. Ви відкриваєте гру й майже одразу розумієте ідею. Без класичного хаосу слотів, барабанів і заплутаних символів. Це схоже на невелику аркаду, але кожен наступний крок усе одно несе ризик.",
            "Ось чому Chicken Road так добре працює. Вона більше нагадує аркаду, ніж класичну казино-гру. Ви бачите курку, дорогу, машини — і головне рішення завжди зрозуміле: залишитися з поточним результатом чи спробувати ще один крок.",
            "InOut Games зібрали тут те, що подобається різним типам гравців. Гра виглядає легкою й веселою, правила прості. Розібратися можна швидко, але після старту вона легко утримує увагу.",
            "Безкоштовна версія на нашому сайті якраз для цього — щоб ви самі спробували Chicken Road. Відкрийте демо, грайте на віртуальний баланс, тестуйте механіку й насолоджуйтесь процесом, не витрачаючи ні цента.",
            "Це хороший спосіб зрозуміти, чому гра стала такою популярною, перш ніж переходити до гри на реальні гроші.",
        ],
        "what_h2": "Що таке демо-режим Chicken Road?",
        "what_paras": [
            "Chicken Road Demo — безкоштовна версія гри. Ви граєте на віртуальний баланс, а не на реальні гроші. У цьому весь сенс. Можна відкрити гру, поставити тестову ставку, вибрати рівень складності й подивитися, як курка рухається дорогою, не боячись втратити власні кошти. Помилилися — нічого страшного: втрачаються лише демо-кредити, і ви починаєте знову.",
            "Демо — це не інша гра. Вона працює майже так само, як повна версія. Курка йде крок за кроком, множник зростає після кожного безпечного ходу, і вам усе одно потрібно вирішувати, коли зупинитися. Забрати виграш зараз чи ризикнути ще одним кроком — у цьому вся напруга Chicken Road.",
            "Новачкам це найкращий старт. Не потрібно вносити депозит, реєструватися чи переживати за баланс. Можна перевірити, як працює кнопка Cash Out, і побачити, як швидко раунд може перейти від безпечного до програного.",
            "І це важливо: демо корисне, але не робить вас сильнішими за саму гру. Кілька вдалих раундів на віртуальних кредитах не означають, що ви знайшли виграшну систему. У демо люди зазвичай грають вільніше: ризикують більше, чекають довше й менше хвилюються через програш.",
            "З реальними грошима все інакше. Той самий крок відчувається по-іншому, коли на кону ваш власний баланс. Використовуйте Chicken Road Demo за призначенням — вивчайте гру, тестуйте кнопки й розумійте ризик, перш ніж думати про реальні ставки.",
        ],
        "start_h2": "Як запустити демо Chicken Road",
        "start_steps": [
            "Запустити демо Chicken Road просто. За бажанням завантажте й установіть застосунок — після встановлення відкрийте його. Також можна грати прямо в браузері на сторінці нашого сайту.",
            "Перед стартом виберіть рівень складності. Почніть із Easy, якщо хочете просто зрозуміти гру, або перейдіть на вищі режими, коли захочете побачити, як змінюється ризик.",
            "Потім задайте віртуальну ставку, щоб протестувати різні суми й подивитися, як працює множник у раунді.",
            "Ведіть курку вперед крок за кроком. Якщо крок безпечний, множник зростає. У будь-який момент можна натиснути Cash Out і забрати поточний віртуальний виграш.",
        ],
        "start_summary": "Увесь процес простий: відкрийте демо, виберіть режим, задайте ставку, рухайтесь вперед і зупиніться, коли ризик здасться достатнім.",
        "vs_h2": "Демо та гра на реальні гроші",
        "vs_paras": [
            "Сама гра майже не змінюється. Та сама курка, та сама дорога, ті самі кнопки, та сама ідея. Відмінність — у балансі.",
            "У демо ви граєте на віртуальні кредити. Можна їх програти, почати заново, спробувати іншу складність — і ваші гроші не постраждають. Тому в демо зазвичай грають вільніше: роблять зайві кроки, тестують високий ризик і натискають «Go», просто щоб подивитися, що буде.",
            "З реальними грошима відчуття інші. Навіть те саме рішення здається важчим. Ви думаєте про ставку, про програш, про шанс відігратися. Хтось забирає виграш занадто рано через нерви. Хтось іде занадто далеко заради більшого результату.",
            "Технічно різниця проста: віртуальний баланс у демо, реальні кошти в режимі на гроші. Але емоційно це не та сама гра. Демо — для навчання. Гра на реальні гроші додає тиску, а тиск швидко змінює рішення.",
        ],
        "why_h2": "Навіщо починати з демо?",
        "why_before": [
            "Демо — найбезпечніший спосіб зрозуміти Chicken Road, перш ніж використовувати реальні гроші. Там можна помилятися — у цьому й сенс.",
            "У безкоштовному режимі можна протестувати базову механіку без тиску. Виберіть складність, поставте віртуальну ставку, ведіть курку вперед і відчуйте, як проходить раунд. Після кількох спроб гра стає набагато зрозумілішою.",
        ],
        "why_bullets_intro": "Демо допомагає з кількома речами:",
        "why_bullets": [
            "зрозуміти, як працює гра;",
            "побачити, як складність змінює ризик;",
            "звикнути до кнопки Cash Out;",
            "перевірити свій стиль гри;",
            "помітити, як швидко зростає ризик;",
            "грати, не переживаючи за реальний баланс.",
        ],
        "why_after": [
            "Це також гарне місце, щоб перевірити свою поведінку. Хтось забирає виграш рано. Хтось натискає «ще один крок». Хтось одразу йде в Hardcore, щоб подивитися, що буде. У демо все це нормально, бо баланс віртуальний.",
            "Гра на реальні гроші інша. Там те саме рішення відчувається важче. Тому перед ризиком має сенс провести час у демо й нормально розібратися в грі.",
        ],
        "mobile_h2": "Демо Chicken Road на мобільному",
        "mobile_paras": [
            "Демо Chicken Road чудово працює на телефоні. Гра проста, їй не потрібен величезний екран. Ви відкриваєте її, бачите курку, дорогу, суму ставки й основні кнопки — цього достатньо.",
            "На мобільному екран вертикальний, тому гра виглядає трохи інакше. Дорога не така широка, як на десктопі. Деякі елементи зближені, а меню складності зазвичай сховане в одній кнопці.",
            "Кнопки все одно зручні. Задайте віртуальну ставку, натисніть Play, ведіть курку вперед і натискайте Cash Out, коли не хочете ризикувати наступним кроком. Нічого складного.",
            "Швидкість завантаження залежить від телефона й інтернету. Новий пристрій відкриє демо швидше. Старий телефон або слабкий сигнал можуть трохи уповільнити старт. Але це не гра, де треба панікувати й клікати щосекунди — після безпечного кроку можна зупинитися й подумати.",
            "Можна грати в демо прямо з браузера. Застосунок не обов’язковий, але для швидшого доступу скористайтеся ярликом з нашого сайту. Він додасть іконку Chicken Road Demo на головний екран, і наступного разу не доведеться знову шукати сторінку.",
        ],
        "download_h2": "Чи потрібно завантажувати демо Chicken Road?",
        "download_paras": [
            "Для гри в демо Chicken Road завантаження не обов’язкове. Демо працює через веб — можна відкрити його в браузері й одразу грати на віртуальний баланс.",
            "На нашому сайті також може бути швидкий доступ через ярлик застосунку або PWA. На телефоні це виглядає й відчувається як застосунок, але демо все одно працює через сайт. Головне — зручність: натиснули іконку й швидше відкрили Chicken Road, без пошуку сторінки.",
            "Обережніше з випадковими APK з інших сайтів. Якщо якась сторінка пропонує «особливий Chicken Road APK», «зламану версію» або «застосунок-передбачувач», краще пропустити. Такі файли можуть бути підробкою й іноді створені лише для збору даних або переведення на небезпечні сторінки.",
            "Для звичайної гри в демо достатньо браузерної версії. Відкрийте сторінку, запустіть гру й протестуйте Chicken Road без ризикованого встановлення.",
        ],
        "trouble_h2": "Якщо демо Chicken Road не запускається",
        "trouble_paras": [
            "Іноді демо просто не стартує. Великої таємниці тут немає — частіше за все справа в браузері чи інтернеті, а не в грі.",
            "Почніть із простого: оновіть сторінку. Якщо індикатор завантаження завис, закрийте вкладку й відкрийте знову. Усе ще нічого? Спробуйте інший браузер. Chrome, Safari, Firefox, Samsung Internet — зазвичай один із них працює краще.",
            "Якщо кнопка Play видна, але нічого не відбувається, зачекайте трохи. Кнопка може з’явитися раніше, ніж гра повністю завантажиться. Це частіше на мобільному інтернеті або старих телефонах.",
            "Зависання телефона теж зазвичай вирішуються просто. Перевірте, що може перевантажувати систему пристрою.",
            "Немає віртуального балансу на екрані? Уважно подивіться в правий верхній кут — там має відображатися ваш баланс.",
            "Блокувальник реклами теж може ламати сторінку. Деякі помилково блокують ігрові скрипти. Вимкніть його для цієї сторінки або спробуйте приватний режим. І перевірте інтернет: демо Chicken Road не важке, але слабкий сигнал усе одно може залишити гру на завантаженні до першого раунду.",
        ],
        "safety_h2": "Безпека демо та чесна гра",
        "safety_paras": [
            "Chicken Road Demo створено для одного — щоб ви спробували гру до використання реальних грошей. Ви відкриваєте її, граєте на віртуальний баланс, тестуєте кнопки й дивитеся, як працює раунд. Демо має робити лише це.",
            "Для безкоштовної версії не повинні вимагати номер картки, CVV, SMS-підтвердження, паспортні дані чи будь-яку платіжну інформацію. Якщо сайт просить депозит перед відкриттям «демо-режиму», це вже поганий знак.",
            "Демо на нашому сайті — для практики й базового знайомства з грою. Можна перевірити механіку, спробувати різні рівні складності й зрозуміти, як змінюється множник після кожного безпечного кроку. Для цього реальна оплата не потрібна.",
            "Обережніше зі сторінками, які використовують назву Chicken Road, але обіцяють «гарантовані виграші», «таємне передбачення» або «особливе демо з реальними виплатами». Звичайне демо так не працює. Воно використовує віртуальні кредити, і будь-який результат у демо залишається в демо.",
            "Просте правило: якщо це справді безкоштовне демо, воно не повинно просити гроші.",
        ],
        "faq_h2": "FAQ — демо Chicken Road",
        "faq": [
            (
                "Чи можна грати в Chicken Road Demo безкоштовно?",
                "Так. Демо на нашому сайті безкоштовне, і ви не ризикуєте реальними грошима.",
            ),
            (
                "Чи потрібна реєстрація?",
                "Ні. Для тесту гри реєстрація не потрібна.",
            ),
            (
                "Чи можна виграти реальні гроші в демо?",
                "Ні. Виграші в демо залишаються в демо. Можна наростити віртуальний баланс, програти його, почати знову — але вивести нічого не можна.",
            ),
            (
                "Чи демо-режим такий самий, як гра на гроші?",
                "Геймплей той самий. Та сама курка, та сама дорога, ті самі рівні складності, та сама логіка виведення. Відмінність — у балансі: у демо він віртуальний, у режимі на гроші — ваш власний.",
            ),
            (
                "Чи працює Chicken Road Demo на мобільному?",
                "Так. Гра з самого початку проєктувалася з урахуванням мобільної версії.",
            ),
            (
                "Чи потрібно щось завантажувати?",
                "Ні. Демо можна грати в браузері. Якщо потрібен швидший доступ, скористайтеся ярликом з нашого сайту, але це не обов’язково.",
            ),
            (
                "Чи можна передбачити наступний крок?",
                "Ні. Заздалегідь не можна знати, що станеться на наступному ході. Будь-який застосунок або сайт із «передбаченнями Chicken Road» варто сприймати обережно.",
            ),
            (
                "Чи є стратегія для демо-режиму?",
                "У демо можна тестувати різні стилі: ранній вивід, гра лише на Easy або вища складність, щоб відчути ризик. Але демо не розкриває гарантованої системи.",
            ),
            (
                "Це офіційна гра Chicken Road?",
                "Chicken Road розроблена InOut Games. Демо на нашому сайті доступне для практики й знайомства з грою. Наш сайт не є офіційним сайтом InOut Games.",
            ),
        ],
        "titles": {
            "start_steps_title": "Як почати: чотири кроки",
            "step1_h": "1. Відкрити",
            "step1_p": "Запустіть демо в браузері або через ярлик з нашого сайту.",
            "step2_h": "2. Налаштувати",
            "step2_p": "Виберіть складність і задайте віртуальну ставку перед раундом.",
            "step3_h": "3. Cash Out",
            "step3_p": "Ведіть курку вперед і забирайте множник, коли ризик здається достатнім.",
        },
        "alts": {
            "gameplay": "Демо-геймплей Chicken Road із множником на екрані",
            "app": "Демо Chicken Road на телефоні та комп’ютері",
            "mobile": "Мобільний інтерфейс Chicken Road у портретному режимі",
            "interface": "Інтерфейс Chicken Road на різних пристроях",
            "step1": "Крок 1: відкрити демо Chicken Road",
            "step2": "Крок 2: вибрати складність і ставку",
            "step3": "Крок 3: виведення виграшу в демо Chicken Road",
        },
    }


def _ro() -> dict:
    return {
        "intro_h2": "Chicken Road Demo — joacă gratuit fără risc",
        "intro_paras": [
            "Chicken Road a devenit populară pentru că la început nu te obligă să te gândești prea mult. Deschizi jocul și înțelegi ideea aproape imediat. Fără haosul clasic al sloturilor, fără role care se învârt, fără simboluri confuze. Arată ca un arcade mic, dar fiecare pas următor poartă totuși risc.",
            "De aceea Chicken Road funcționează atât de bine. Se simte mai degrabă ca un joc arcade decât ca un titlu clasic de cazino. Vezi găina, drumul, mașinile — iar decizia principală rămâne clară: păstrezi rezultatul actual sau încerci încă un pas.",
            "InOut Games au reușit să adune ce atrage tipuri diferite de jucători. Jocul pare ușor și amuzant, regulile sunt simple. Înțelegi repede, dar după ce ai început îți ține ușor atenția.",
            "Versiunea gratuită de pe site-ul nostru e făcută exact pentru asta — să încerci Chicken Road pe cont propriu. Deschizi demo-ul, joci cu sold virtual, testezi mecanica și te bucuri de joc fără să cheltui un cent.",
            "E un mod bun să înțelegi de ce jocul a devenit atât de popular înainte să treci la jocul pe bani reali.",
        ],
        "what_h2": "Ce este modul demo Chicken Road?",
        "what_paras": [
            "Chicken Road Demo este versiunea gratuită a jocului. Joci cu sold virtual, nu cu bani reali. Asta e ideea principală. Poți deschide jocul, pune un pariu de test, alege dificultatea și vedea cum găina avansează pe drum fără teama să-ți pierzi banii. Dacă greșești, nu se întâmplă nimic grav — pierzi doar credite demo și reîncepi.",
            "Demo-ul nu e alt joc. Funcționează aproape la fel ca versiunea completă. Găina merge pas cu pas, multiplicatorul crește după fiecare pas sigur și tot trebuie să decizi când te oprești. Cash out acum sau încă un pas — asta e toată tensiunea Chicken Road.",
            "Pentru începători, e cel mai bun start. Nu ai nevoie de depozit, înregistrare sau griji despre sold. Poți verifica butonul Cash Out și vedea cât de repede o rundă poate trece de la sigur la pierdut.",
            "Și asta contează: modul demo e util, dar nu te face mai bun decât jocul. Câteva runde bune cu credite virtuale nu înseamnă că ai găsit un sistem câștigător. În demo oamenii joacă de obicei mai liber: riscă mai mult, așteaptă mai mult și nu le pasă mult de pierdere.",
            "Banii reali schimbă totul. Același pas se simte diferit când pe masă e propriul tău sold. Folosește Chicken Road Demo pentru ce face bine — învață jocul, testează butoanele și înțelege riscul înainte să te gândești la pariuri reale.",
        ],
        "start_h2": "Cum pornești demo-ul Chicken Road",
        "start_steps": [
            "Pornirea demo-ului Chicken Road e simplă. Dacă vrei, descarcă și instalează aplicația — după instalare o deschizi. Poți juca și direct în browser pe pagina site-ului nostru.",
            "Înainte de start, alege nivelul de dificultate. Începe cu Easy dacă vrei doar să înțelegi jocul, sau treci la moduri mai grele când vrei să vezi cum se schimbă riscul.",
            "Apoi setează pariul virtual ca să testezi sume diferite și să vezi cum funcționează multiplicatorul în rundă.",
            "Avansează găina pas cu pas. Dacă pasul e sigur, multiplicatorul crește. Oricând poți apăsa Cash Out și lua câștigul virtual curent.",
        ],
        "start_summary": "Tot procesul e simplu: deschide demo-ul, alege modul, setează pariul, avansează și oprește-te când simți că riscul e suficient.",
        "vs_h2": "Demo vs joc pe bani reali",
        "vs_paras": [
            "Jocul în sine aproape nu se schimbă. Aceeași găină, același drum, aceleași butoane, aceeași idee. Diferența e soldul.",
            "În demo joci cu credite virtuale. Le poți pierde, reporni, încerca altă dificultate — banii tăi nu sunt afectați. De aceea în demo se joacă de obicei mai liber: pași în plus, risc mai mare, apasă «Go» doar ca să vadă ce se întâmplă.",
            "Cu bani reali senzația e alta. Chiar și aceeași decizie pare mai grea. Te gândești la pariu, la pierdere, la șansa de recuperare. Unii fac cash out prea devreme din nervi. Alții merg prea departe pentru un rezultat mai mare.",
            "Tehnic, diferența e simplă: sold virtual în demo, fonduri reale în modul pe bani reali. Dar emoțional nu e același joc. Demo-ul e pentru învățare. Jocul pe bani reali adaugă presiune, iar presiunea schimbă deciziile repede.",
        ],
        "why_h2": "De ce să începi cu demo-ul?",
        "why_before": [
            "Demo-ul e cel mai sigur mod să înțelegi Chicken Road înainte de bani reali. Poți greși acolo — asta e scopul.",
            "În modul gratuit testezi mecanica de bază fără presiune. Alege dificultatea, pune un pariu virtual, avansează găina și simte cum merge runda. După câteva încercări jocul devine mult mai clar.",
        ],
        "why_bullets_intro": "Demo-ul ajută la câteva lucruri:",
        "why_bullets": [
            "să înțelegi cum funcționează jocul;",
            "să vezi cum dificultatea schimbă riscul;",
            "să te obișnuiești cu butonul Cash Out;",
            "să îți testezi stilul de joc;",
            "să observi cât de repede crește riscul;",
            "să joci fără griji despre soldul real.",
        ],
        "why_after": [
            "E și un loc bun să-ți verifici comportamentul. Unii fac cash out devreme. Alții apasă «încă un pas». Alții merg direct la Hardcore doar ca să vadă. În demo totul e în regulă, pentru că soldul e virtual.",
            "Jocul pe bani reali e diferit. Acolo aceeași decizie se simte mai grea. Înainte să riști ceva, are sens să petreci timp în demo și să înțelegi jocul cum trebuie.",
        ],
        "mobile_h2": "Demo Chicken Road pe mobil",
        "mobile_paras": [
            "Demo-ul Chicken Road merge bine pe telefon. Jocul e simplu și nu are nevoie de ecran uriaș. Îl deschizi, vezi găina, drumul, suma pariului și butoanele principale — e suficient.",
            "Pe mobil ecranul e vertical, deci jocul arată puțin diferit. Drumul nu e la fel de lat ca pe desktop. Unele elemente sunt mai apropiate, iar meniul de dificultate e de obicei într-un singur buton.",
            "Butoanele sunt tot ușor de folosit. Setează un pariu virtual, apasă Play, avansează găina și apasă Cash Out când nu vrei să riști pasul următor. Nimic complicat.",
            "Viteza de încărcare depinde de telefon și internet. Un dispozitiv nou deschide demo-ul mai repede. Un telefon vechi sau semnal slab îl pot încetini puțin. Dar nu e un joc în care trebuie să apeși în panică în fiecare secundă — după un pas sigur poți opri și gândi.",
            "Poți juca demo-ul direct din browser. Aplicația nu e obligatorie, dar pentru acces mai rapid folosește scurtătura de pe site-ul nostru. Adaugă iconița Chicken Road Demo pe ecranul principal, ca data viitoare să nu cauți pagina.",
        ],
        "download_h2": "Trebuie să descarci demo-ul Chicken Road?",
        "download_paras": [
            "Nu e nevoie să descarci Chicken Road Demo ca să joci. Demo-ul merge prin web — îl deschizi în browser și joci imediat cu sold virtual.",
            "Pe site-ul nostru poate exista și acces rapid printr-o scurtătură de tip app sau PWA. Pe telefon arată și se simte ca o aplicație, dar demo-ul tot rulează prin site. Ideea e confortul: atingi iconița și deschizi Chicken Road mai repede, fără să cauți pagina.",
            "Ai grijă la fișiere APK aleatoare de pe alte site-uri. Dacă o pagină oferă «APK special Chicken Road», «versiune hack» sau «app predictor», mai bine sari peste. Fișierele pot fi false și uneori sunt făcute doar să colecteze date sau să ducă pe pagini nesigure.",
            "Pentru joc demo obișnuit, versiunea din browser e suficientă. Deschide pagina, lansează jocul și testează Chicken Road fără instalări riscante.",
        ],
        "trouble_h2": "Dacă demo-ul Chicken Road nu pornește",
        "trouble_paras": [
            "Uneori demo-ul pur și simplu nu pornește. Nu e mare mister — de cele mai multe ori e browserul sau internetul, nu jocul.",
            "Începe simplu: reîmprospătează pagina. Dacă încărcarea e blocată, închide tab-ul și deschide din nou. Tot nimic? Încearcă alt browser. Chrome, Safari, Firefox, Samsung Internet — de obicei unul merge mai bine.",
            "Dacă butonul Play e vizibil dar nu face nimic, așteaptă puțin. Butonul poate apărea înainte ca jocul să se încarce complet. Se întâmplă mai des pe date mobile sau telefoane vechi.",
            "Înghețările telefonului sunt de obicei simple de rezolvat. Verifică ce poate suprasolicita sistemul dispozitivului.",
            "Fără sold virtual pe ecran? Uită-te atent în colțul din dreapta sus — soldul ar trebui afișat acolo.",
            "Blocatorii de reclame pot strica pagina. Unii blochează din greșeală scripturile jocului. Dezactivează blocarea pentru această pagină sau încearcă modul privat. Și verifică internetul: demo-ul nu e greu, dar semnal slab poate lăsa jocul blocat înainte de prima rundă.",
        ],
        "safety_h2": "Siguranța demo și joc corect",
        "safety_paras": [
            "Chicken Road Demo e făcut pentru un lucru — să încerci jocul înainte de bani reali. Îl deschizi, joci cu sold virtual, testezi butoanele și vezi cum merge runda. Asta ar trebui să facă un demo.",
            "Pentru versiunea gratuită nu ar trebui să introduci număr de card, CVV, SMS, pașaport sau date de plată. Dacă un site cere depozit înainte de «mod demo», e deja semn rău.",
            "Demo-ul de pe site-ul nostru e pentru practică și familiarizare. Poți verifica mecanica, încerca niveluri de dificultate și înțelege cum se schimbă multiplicatorul după fiecare pas sigur. Nu e nevoie de plată reală.",
            "Ai grijă la pagini care folosesc numele Chicken Road dar promit «câștiguri garantate», «predicție secretă» sau «demo special cu plăți reale». Un demo normal nu funcționează așa — folosește credite virtuale, iar orice rezultat rămâne în demo.",
            "Regulă simplă: dacă e cu adevărat demo gratuit, nu ar trebui să ceară bani.",
        ],
        "faq_h2": "FAQ — demo Chicken Road",
        "faq": [
            (
                "Pot juca Chicken Road Demo gratuit?",
                "Da. Demo-ul de pe site-ul nostru e gratuit și nu riști bani reali.",
            ),
            (
                "Trebuie să mă înregistrez?",
                "Nu. Pentru a testa jocul nu e nevoie de înregistrare.",
            ),
            (
                "Pot câștiga bani reali în demo?",
                "Nu. Câștigurile din demo rămân în demo. Poți crește soldul virtual, pierde, reporni — dar nu poți retrage nimic.",
            ),
            (
                "Modul demo e la fel ca jocul pe bani reali?",
                "Gameplay-ul e același. Aceeași găină, drum, niveluri de dificultate, aceeași logică de cash out. Diferența e soldul: virtual în demo, al tău în modul pe bani reali.",
            ),
            (
                "Chicken Road Demo merge pe mobil?",
                "Da. Jocul a fost proiectat de la început cu versiunea mobilă în minte.",
            ),
            (
                "Trebuie să descarc ceva?",
                "Nu. Demo-ul se joacă în browser. Pentru acces mai rapid poți folosi scurtătura de pe site, dar nu e obligatoriu.",
            ),
            (
                "Se poate prezice pasul următor?",
                "Nu. Nu poți ști dinainte ce se întâmplă la mutarea următoare. Orice app sau site care promite «predicții Chicken Road» merită tratat cu prudență.",
            ),
            (
                "Există strategie pentru modul demo?",
                "Poți testa stiluri diferite: cash out devreme, doar Easy sau dificultate mai mare ca să simți riscul. Dar demo-ul nu dezvăluie un sistem garantat.",
            ),
            (
                "Acesta e jocul oficial Chicken Road?",
                "Chicken Road e dezvoltat de InOut Games. Demo-ul de pe site-ul nostru e pentru practică și familiarizare. Site-ul nostru nu e site-ul oficial InOut Games.",
            ),
        ],
        "titles": {
            "start_steps_title": "Cum să începi: patru pași",
            "step1_h": "1. Deschide",
            "step1_p": "Pornește demo-ul în browser sau prin scurtătura de pe site-ul nostru.",
            "step2_h": "2. Configurează",
            "step2_p": "Alege dificultatea și setează pariul virtual înainte de rundă.",
            "step3_h": "3. Cash Out",
            "step3_p": "Avansează găina și ia multiplicatorul când riscul ți se pare suficient.",
        },
        "alts": {
            "gameplay": "Gameplay demo Chicken Road cu multiplicator pe ecran",
            "app": "Demo Chicken Road pe telefon și computer",
            "mobile": "Interfața mobilă Chicken Road în mod portret",
            "interface": "Interfața Chicken Road pe mai multe dispozitive",
            "step1": "Pasul 1: deschide demo Chicken Road",
            "step2": "Pasul 2: alege dificultatea și pariul",
            "step3": "Pasul 3: cash out în demo Chicken Road",
        },
    }
