#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""About Us (pages#26) — Ice Fish content for all locales."""

from __future__ import annotations


def _wrap(body: str) -> str:
    return f'<div class="about_content">\n{body.strip()}\n</div>'


LOCALE_CONTENT: dict[str, str] = {
    "en": _wrap("""
<h1>About Ice Fish</h1>
<p><strong>Ice Fish</strong> is an independent informational resource about the <em>Ice Fish</em> step game (InOut Games), licensed casinos that host the game, and related topics&mdash;demo play, guides, bonuses, and safer gambling.</p>
<p>We do <strong>not</strong> operate a casino or sportsbook. We do not register players, hold funds, or pay winnings. Any real-money play happens only on third-party sites you choose; we may link to them for reference.</p>
<h2>Who this site is for</h2>
<p>Content is aimed at adults who are legally allowed to gamble in their jurisdiction. If you are unsure, do not use gambling sites.</p>
<h2>Accuracy</h2>
<p>We aim to keep information up to date, but bonuses, terms, and availability change. Always verify critical details on the operator&rsquo;s official website before you deposit or play.</p>
<h2>Affiliate disclosure</h2>
<p>Some links may be affiliate links. That can mean we receive a commission if you sign up or deposit&mdash;at no extra cost to you. This does not affect our editorial independence, but you should assume commercial relationships may exist.</p>
<h2>Contact</h2>
<p>For factual corrections or business enquiries, please use the contact method published on this site (if provided).</p>
"""),
    "fr": _wrap("""
<h1>&Agrave; propos de Ice Fish</h1>
<p><strong>Ice Fish</strong> est un site d&rsquo;information ind&eacute;pendant sur le jeu par &eacute;tapes <em>Ice Fish</em> (InOut Games), les casinos en ligne qui le proposent, ainsi que la d&eacute;mo, les guides, les bonus et le jeu responsable.</p>
<p>Nous ne sommes <strong>pas</strong> un op&eacute;rateur de jeux d&rsquo;argent. Nous n&rsquo;enregistrons pas les joueurs, ne d&eacute;tenons pas de fonds et ne versons pas de gains. Toute mise en argent r&eacute;el se fait uniquement sur les sites tiers que vous choisissez ; nous pouvons y renvoyer par des liens.</p>
<h2>Public vis&eacute;</h2>
<p>Le contenu s&rsquo;adresse aux adultes autoris&eacute;s &agrave; jouer dans leur pays. En cas de doute sur la l&eacute;galit&eacute;, n&rsquo;utilisez pas les sites de paris.</p>
<h2>Exactitude des informations</h2>
<p>Nous nous effor&ccedil;ons de maintenir des informations &agrave; jour, mais bonus, conditions et disponibilit&eacute; &eacute;voluent. V&eacute;rifiez toujours les d&eacute;tails sur le site officiel de l&rsquo;op&eacute;rateur avant de d&eacute;poser ou de jouer.</p>
<h2>Liens d&rsquo;affiliation</h2>
<p>Certains liens peuvent &ecirc;tre des liens d&rsquo;affiliation : nous pouvons percevoir une commission si vous vous inscrivez ou d&eacute;posez, sans surco&ucirc;t pour vous. Cela n&rsquo;emp&ecirc;che pas une r&eacute;daction ind&eacute;pendante, mais des relations commerciales peuvent exister.</p>
<h2>Contact</h2>
<p>Pour signaler une erreur ou pour un partenariat, utilisez les coordonn&eacute;es indiqu&eacute;es sur le site (si disponibles).</p>
"""),
    "de": _wrap("""
<h1>&Uuml;ber Ice Fish</h1>
<p><strong>Ice Fish</strong> ist ein unabh&auml;ngiger Informationsdienst &uuml;ber das Schrittspiel <em>Ice Fish</em> (InOut Games), lizenzierte Casinos, die es anbieten, sowie Demo-Spiel, Anleitungen, Boni und sicheres Gl&uuml;cksspiel.</p>
<p>Wir betreiben <strong>kein</strong> Casino und keine Sportwetten. Wir registrieren keine Spieler, halten kein Geld und zahlen keine Gewinne aus. Echtgeldspiel findet nur auf von Ihnen gew&auml;hlten Drittseiten statt; wir k&ouml;nnen dorthin verlinken.</p>
<h2>F&uuml;r wen diese Seite ist</h2>
<p>Der Inhalt richtet sich an Erwachsene, die in ihrem Rechtsraum legal spielen d&uuml;rfen. Wenn Sie unsicher sind, nutzen Sie keine Gl&uuml;cksspielseiten.</p>
<h2>Genauigkeit</h2>
<p>Wir halten Informationen m&ouml;glichst aktuell, aber Boni, Bedingungen und Verf&uuml;gbarkeit &auml;ndern sich. Pr&uuml;fen Sie wichtige Details immer auf der offiziellen Website des Anbieters, bevor Sie einzahlen oder spielen.</p>
<h2>Affiliate-Hinweis</h2>
<p>Einige Links k&ouml;nnen Affiliate-Links sein. Das kann bedeuten, dass wir eine Provision erhalten, wenn Sie sich anmelden oder einzahlen &mdash; ohne Mehrkosten f&uuml;r Sie. Das beeinflusst unsere redaktionelle Unabh&auml;ngigkeit nicht, kommerzielle Beziehungen sind jedoch m&ouml;glich.</p>
<h2>Kontakt</h2>
<p>F&uuml;r sachliche Korrekturen oder Gesch&auml;ftsanfragen nutzen Sie bitte die auf dieser Seite ver&ouml;ffentlichte Kontaktm&ouml;glichkeit (falls vorhanden).</p>
"""),
    "es": _wrap("""
<h1>Acerca de Ice Fish</h1>
<p><strong>Ice Fish</strong> es un recurso informativo independiente sobre el juego por pasos <em>Ice Fish</em> (InOut Games), casinos con licencia que lo ofrecen y temas relacionados: demo, gu&iacute;as, bonos y juego responsable.</p>
<p><strong>No</strong> operamos un casino ni una casa de apuestas. No registramos jugadores, no guardamos fondos ni pagamos premios. El juego con dinero real solo ocurre en sitios de terceros que elijas; podemos enlazarlos como referencia.</p>
<h2>&iquest;Para qui&eacute;n es este sitio?</h2>
<p>El contenido va dirigido a adultos con permiso legal para jugar en su jurisdicci&oacute;n. Si tienes dudas, no uses sitios de apuestas.</p>
<h2>Exactitud</h2>
<p>Procuramos mantener la informaci&oacute;n al d&iacute;a, pero bonos, t&eacute;rminos y disponibilidad cambian. Verifica siempre los detalles clave en la web oficial del operador antes de depositar o jugar.</p>
<h2>Divulgaci&oacute;n de afiliados</h2>
<p>Algunos enlaces pueden ser de afiliado. Eso puede significar que recibimos comisi&oacute;n si te registras o depositas, sin coste extra para ti. No afecta nuestra independencia editorial, pero pueden existir relaciones comerciales.</p>
<h2>Contacto</h2>
<p>Para correcciones o consultas comerciales, usa el m&eacute;todo de contacto publicado en este sitio (si est&aacute; disponible).</p>
"""),
    "hi": _wrap("""
<h1>Ice Fish के बारे में</h1>
<p><strong>Ice Fish</strong> <em>Ice Fish</em> स्टेप गेम (InOut Games), इसे होस्ट करने वाले लाइसेंस प्राप्त कैसिनो और संबंधित विषयों—डेमो, गाइड, बोनस और सुरक्षित खेल—के बारे में एक स्वतंत्र जानकारी संसाधन है।</p>
<p>हम कैसिनो या स्पोर्ट्सबुक <strong>नहीं</strong> चलाते। हम खिलाड़ियों को रजिस्टर नहीं करते, पैसे नहीं रखते और जीत का भुगतान नहीं करते। असली पैसे का खेल केवल आपके चुने तीसरे पक्ष की साइटों पर होता है; हम संदर्भ के लिए उनकी ओर लिंक कर सकते हैं।</p>
<h2>यह साइट किसके लिए है</h2>
<p>सामग्री उन वयस्कों के लिए है जो अपने क्षेत्र में कानूनी रूप से जुआ खेल सकते हैं। यदि आप अनिश्चित हैं, तो जुआ साइटों का उपयोग न करें।</p>
<h2>सटीकता</h2>
<p>हम जानकारी अद्यतन रखने का प्रयास करते हैं, लेकिन बोनस, शर्तें और उपलब्धता बदलती रहती हैं। जमा या खेलने से पहले ऑपरेटर की आधिकारिक साइट पर महत्वपूर्ण विवरण की पुष्टि करें।</p>
<h2>अफिलिएट प्रकटीकरण</h2>
<p>कुछ लिंक अफिलिएट लिंक हो सकते हैं। इसका मतलब हो सकता है कि यदि आप साइन अप या जमा करते हैं तो हमें कमीशन मिले—आपके लिए अतिरिक्त लागत के बिना। यह संपादकीय स्वतंत्रता को प्रभावित नहीं करता, लेकिन व्यावसायिक संबंध हो सकते हैं।</p>
<h2>संपर्क</h2>
<p>तथ्यात्मक सुधार या व्यावसायिक पूछताछ के लिए, कृपया इस साइट पर प्रकाशित संपर्क विधि का उपयोग करें (यदि उपलब्ध हो)।</p>
"""),
    "pt": _wrap("""
<h1>Sobre o Ice Fish</h1>
<p><strong>Ice Fish</strong> &eacute; um recurso informativo independente sobre o jogo por passos <em>Ice Fish</em> (InOut Games), cassinos licenciados que o oferecem e temas relacionados &mdash; demo, guias, b&oacute;nus e jogo respons&aacute;vel.</p>
<p><strong>N&atilde;o</strong> operamos cassino nem casa de apostas. N&atilde;o registamos jogadores, n&atilde;o guardamos fundos nem pagamos pr&eacute;mios. Jogo a dinheiro real s&oacute; acontece em sites de terceiros que escolher; podemos ligar a eles como refer&ecirc;ncia.</p>
<h2>Para quem &eacute; este site</h2>
<p>O conte&uacute;do destina-se a adultos legalmente autorizados a jogar na sua jurisdi&ccedil;&atilde;o. Se tiver d&uacute;vidas, n&atilde;o use sites de apostas.</p>
<h2>Exatid&atilde;o</h2>
<p>Procuramos manter a informa&ccedil;&atilde;o atualizada, mas b&oacute;nus, termos e disponibilidade mudam. Verifique sempre detalhes importantes no site oficial do operador antes de depositar ou jogar.</p>
<h2>Divulga&ccedil;&atilde;o de afiliados</h2>
<p>Alguns links podem ser de afiliado. Isso pode significar que recebemos comiss&atilde;o se se registar ou depositar &mdash; sem custo extra para si. Isso n&atilde;o afeta a nossa independ&ecirc;ncia editorial, mas rela&ccedil;&otilde;es comerciais podem existir.</p>
<h2>Contacto</h2>
<p>Para correc&ccedil;&otilde;es factuais ou contactos comerciais, use o m&eacute;todo publicado neste site (se dispon&iacute;vel).</p>
"""),
    "ru": _wrap("""
<h1>О Ice Fish</h1>
<p><strong>Ice Fish</strong> &mdash; независимый информационный ресурс о пошаговой игре <em>Ice Fish</em> (InOut Games), лицензированных казино, где она доступна, а также о демо, гайдах, бонусах и ответственной игре.</p>
<p>Мы <strong>не</strong> оператор казино или букмекера. Мы не регистрируем игроков, не храним средства и не выплачиваем выигрыши. Игра на деньги проходит только на сторонних сайтах, которые вы выбираете; мы можем ссылаться на них справочно.</p>
<h2>Для кого этот сайт</h2>
<p>Контент рассчитан на совершеннолетних, которым в их юрисдикции разрешено играть. Если сомневаетесь &mdash; не используйте азартные сайты.</p>
<h2>Точность</h2>
<p>Мы стараемся поддерживать актуальность, но бонусы, условия и доступность меняются. Всегда проверяйте важные детали на официальном сайте оператора перед депозитом или игрой.</p>
<h2>Партнёрское раскрытие</h2>
<p>Некоторые ссылки могут быть партнёрскими. Это может означать комиссию при регистрации или депозите &mdash; без дополнительных расходов для вас. На редакционную независимость это не влияет, но коммерческие отношения возможны.</p>
<h2>Контакты</h2>
<p>Для исправления фактов или деловых запросов используйте контактный способ, опубликованный на сайте (если указан).</p>
"""),
    "ar": _wrap("""
<h1>حول Ice Fish</h1>
<p><strong>Ice Fish</strong> مورد معلومات مستقل عن لعبة الخطوات <em>Ice Fish</em> (InOut Games)، والكازينوهات المرخّصة التي تستضيفها، وموضوعات ذات صلة &mdash; العرض التجريبي، الأدلة، المكافآت واللعب المسؤول.</p>
<p>نحن <strong>لا</strong> نشغّل كازينو أو مراهنات رياضية. لا نسجّل اللاعبين ولا نحتفظ بالأموال ولا ندفع الأرباح. اللعب بمال حقيقي يحدث فقط على مواقع طرف ثالث تختارها؛ قد نربط بها للمرجعية.</p>
<h2>لمن هذا الموقع</h2>
<p>المحتوى موجّه للبالغين المسموح لهم قانوناً بالمقامرة في منطقتهم. إذا لم تكن متأكداً، لا تستخدم مواقع المقامرة.</p>
<h2>الدقة</h2>
<p>نسعى لإبقاء المعلومات محدّثة، لكن المكافآت والشروط والتوفر تتغيّر. تحقق دائماً من التفاصيل المهمة على الموقع الرسمي للمشغّل قبل الإيداع أو اللعب.</p>
<h2>إفصاح الإحالة</h2>
<p>قد تكون بعض الروابط روابط إحالة. قد يعني ذلك أننا نتلقى عمولة إذا سجّلت أو أودعت &mdash; دون تكلفة إضافية عليك. ذلك لا يؤثر على استقلاليتنا التحريرية، لكن قد توجد علاقات تجارية.</p>
<h2>اتصل بنا</h2>
<p>لتصحيحات واقعية أو استفسارات تجارية، استخدم وسيلة الاتصال المنشورة على هذا الموقع (إن وُجدت).</p>
"""),
    "az": _wrap("""
<h1>Ice Fish haqqında</h1>
<p><strong>Ice Fish</strong> <em>Ice Fish</em> addım oyunu (InOut Games), onu təqdim edən lisenziyalı kazinolar və əlaqəli mövzular &mdash; demo, bələdçilər, bonuslar və məsuliyyətli oyun haqqında müstəqil informasiya mənbəyidir.</p>
<p>Biz kazino və ya idman kitabxanası <strong>deyilik</strong>. Oyunçuları qeydiyyatdan keçirmirik, vəsait saxlamırıq və uduş ödəmirik. Real pul oyunu yalnız sizin seçdiyiniz üçüncü tərəf saytlarında baş verir; istinad üçün onlara link verə bilərik.</p>
<h2>Bu sayt kim üçündür</h2>
<p>Məzmun öz yurisdiksiyasında qanuni olaraq oynamağa icazəsi olan yetkinlər üçündür. Əmin deyilsinizsə, qumar saytlarından istifadə etməyin.</p>
<h2>Dəqiqlik</h2>
<p>Məlumatları aktual saxlamağa çalışırıq, lakin bonuslar, şərtlər və mövcudluq dəyişir. Depozit və ya oyun əvvəl həmişə operatorun rəsmi saytında vacib detalları yoxlayın.</p>
<h2>Affiliate açıqlaması</h2>
<p>Bəzi linklər affiliate ola bilər. Qeydiyyat və ya depozit zamanı komissiya ala bilərik &mdash; sizin üçün əlavə xərc olmadan. Redaksiya müstəqilliyinə təsir etmir, lakin kommersiya əlaqələri ola bilər.</p>
<h2>Əlaqə</h2>
<p>Faktiki düzəlişlər və ya biznes sorğuları üçün bu saytda dərc olunan əlaqə üsulundan istifadə edin (əgər varsa).</p>
"""),
    "bn": _wrap("""
<h1>Ice Fish সম্পর্কে</h1>
<p><strong>Ice Fish</strong> <em>Ice Fish</em> স্টেপ গেম (InOut Games), এটি হোস্ট করা লাইসেন্সপ্রাপ্ত ক্যাসিনো এবং সম্পর্কিত বিষয় &mdash; ডেমো, গাইড, বোনাস ও দায়িত্বশীল খেলা &mdash; নিয়ে একটি স্বাধীন তথ্য উৎস।</p>
<p>আমরা ক্যাসিনো বা স্পোর্টসবুক <strong>চালাই না</strong>। আমরা খেলোয়াড় নিবন্ধন করি না, তহবিল রাখি না বা জয় পরিশোধ করি না। আসল টাকার খেলা শুধু আপনার বেছে নেওয়া তৃতীয় পক্ষের সাইটে হয়; আমরা রেফারেন্সের জন্য লিঙ্ক করতে পারি।</p>
<h2>এই সাইট কার জন্য</h2>
<p>কনটেন্ট তাদের এলাকায় আইনগতভাবে জুয়া খেলতে পারা প্রাপ্তবয়স্কদের জন্য। নিশ্চিত না হলে জুয়া সাইট ব্যবহার করবেন না।</p>
<h2>নির্ভুলতা</h2>
<p>আমরা তথ্য হালনাগাদ রাখার চেষ্টা করি, কিন্তু বোনাস, শর্ত ও উপলব্ধতা বদলায়। জমা বা খেলার আগে অপারেটরের অফিসিয়াল সাইটে গুরুত্বপূর্ণ বিবরণ যাচাই করুন।</p>
<h2>অ্যাফিলিয়েট প্রকাশ</h2>
<p>কিছু লিঙ্ক অ্যাফিলিয়েট হতে পারে। নিবন্ধন বা জমায় আমরা কমিশন পেতে পারি &mdash; আপনার অতিরিক্ত খরচ ছাড়াই। এটি সম্পাদকীয় স্বাধীনতাকে প্রভাবিত করে না, তবে বাণিজ্যিক সম্পর্ক থাকতে পারে।</p>
<h2>যোগাযোগ</h2>
<p>তথ্য সংশোধন বা ব্যবসায়িক জিজ্ঞাসার জন্য এই সাইটে প্রকাশিত যোগাযোগ পদ্ধতি ব্যবহার করুন (যদি থাকে)।</p>
"""),
    "it": _wrap("""
<h1>Informazioni su Ice Fish</h1>
<p><strong>Ice Fish</strong> &egrave; una risorsa informativa indipendente sul gioco a passi <em>Ice Fish</em> (InOut Games), i casin&ograve; con licenza che lo ospitano e argomenti correlati &mdash; demo, guide, bonus e gioco responsabile.</p>
<p><strong>Non</strong> gestiamo un casin&ograve; n&eacute; un bookmaker. Non registriamo giocatori, non custodiamo fondi n&eacute; paghiamo vincite. Il gioco con denaro reale avviene solo su siti di terze parti che scegliete; possiamo linkarli come riferimento.</p>
<h2>A chi &egrave; rivolto questo sito</h2>
<p>Il contenuto &egrave; destinato ad adulti autorizzati legalmente a giocare nella propria giurisdizione. In caso di dubbi, non usate siti di scommesse.</p>
<h2>Accuratezza</h2>
<p>Cerchiamo di mantenere le informazioni aggiornate, ma bonus, termini e disponibilit&agrave; cambiano. Verificate sempre i dettagli critici sul sito ufficiale dell&rsquo;operatore prima di depositare o giocare.</p>
<h2>Informativa affiliati</h2>
<p>Alcuni link possono essere affiliati. Potremmo ricevere una commissione se vi registrate o depositate &mdash; senza costi extra per voi. Ci&ograve; non influisce sull&rsquo;indipendenza editoriale, ma relazioni commerciali possono esistere.</p>
<h2>Contatti</h2>
<p>Per correzioni di fatto o richieste commerciali, usate il metodo di contatto pubblicato su questo sito (se disponibile).</p>
"""),
    "nl": _wrap("""
<h1>Over Ice Fish</h1>
<p><strong>Ice Fish</strong> is een onafhankelijke informatiebron over het stapspel <em>Ice Fish</em> (InOut Games), gelicentieerde casino&rsquo;s die het aanbieden en gerelateerde onderwerpen &mdash; demo, gidsen, bonussen en verantwoord spelen.</p>
<p>Wij exploiteren <strong>geen</strong> casino of sportsbook. Wij registreren geen spelers, houden geen geld aan en betalen geen winsten uit. Echt geld spelen gebeurt alleen op door u gekozen sites van derden; wij kunnen ernaar linken ter referentie.</p>
<h2>Voor wie deze site is</h2>
<p>De inhoud is bedoeld voor volwassenen die in hun rechtsgebied legaal mogen gokken. Bij twijfel: gebruik geen goksites.</p>
<h2>Nauwkeurigheid</h2>
<p>We proberen informatie actueel te houden, maar bonussen, voorwaarden en beschikbaarheid wijzigen. Controleer altijd belangrijke details op de offici&euml;le site van de operator v&oacute;&oacute;r u stort of speelt.</p>
<h2>Affiliate-verklaring</h2>
<p>Sommige links kunnen affiliatelinks zijn. Dat kan betekenen dat wij commissie ontvangen als u zich aanmeldt of stort &mdash; zonder extra kosten voor u. Dit heeft geen invloed op onze redactionele onafhankelijkheid, maar commerci&euml;le relaties kunnen bestaan.</p>
<h2>Contact</h2>
<p>Voor feitelijke correcties of zakelijke vragen gebruikt u de contactmethode op deze site (indien beschikbaar).</p>
"""),
    "pl": _wrap("""
<h1>O Ice Fish</h1>
<p><strong>Ice Fish</strong> to niezale&#380;ne &#378;r&oacute;d&#322;o informacji o grze krokowej <em>Ice Fish</em> (InOut Games), licencjonowanych kasynach, kt&oacute;re j&#261; oferuj&#261;, oraz powi&#261;zanych tematach &mdash; demo, przewodnikach, bonusach i bezpiecznym graniu.</p>
<p><strong>Nie</strong> prowadzimy kasyna ani bukmachera. Nie rejestrujemy graczy, nie przechowujemy &#347;rodk&oacute;w ani nie wyp&#322;acamy wygranych. Gra na prawdziwe pieni&#261;dze odbywa si&#281; tylko na wybranych przez Ciebie stronach trzecich; mo&#380;emy do nich linkowa&#263;.</p>
<h2>Dla kogo jest ta strona</h2>
<p>Tre&#347;ci s&#261; skierowane do doros&#322;ych, kt&oacute;rzy legalnie mog&#261; gra&#263; w swojej jurysdykcji. Je&#347;li masz w&#261;tpliwo&#347;ci, nie korzystaj z serwis&oacute;w hazardowych.</p>
<h2>Dok&#322;adno&#347;&#263;</h2>
<p>Staramy si&#281; utrzymywa&#263; aktualne informacje, ale bonusy, warunki i dost&#281;pno&#347;&#263; si&#281; zmieniaj&#261;. Zawsze sprawd&#378; kluczowe szczeg&oacute;&#322;y na oficjalnej stronie operatora przed wp&#322;at&#261; lub gr&#261;.</p>
<h2>Informacja o partnerstwie</h2>
<p>Niekt&oacute;re linki mog&#261; by&#263; partnerskie. Mo&#380;emy otrzyma&#263; prowizj&#281; po rejestracji lub wp&#322;acie &mdash; bez dodatkowych koszt&oacute;w dla Ciebie. Nie wp&#322;ywa to na niezale&#380;no&#347;&#263; redakcyjn&#261;, ale relacje handlowe s&#261; mo&#380;liwe.</p>
<h2>Kontakt</h2>
<p>W sprawie korekt fakt&oacute;w lub zapyta&#324; biznesowych skorzystaj z metody kontaktu opublikowanej na tej stronie (je&#347;li jest dost&#281;pna).</p>
"""),
    "vi": _wrap("""
<h1>Giới thiệu Ice Fish</h1>
<p><strong>Ice Fish</strong> l&agrave; nguồn th&ocirc;ng tin độc lập về game theo bước <em>Ice Fish</em> (InOut Games), c&aacute;c s&ograve;ng bạc c&oacute; giấy ph&eacute;p cung cấp game v&agrave; c&aacute;c chủ đề li&ecirc;n quan &mdash; bản thử, hướng dẫn, khuyến m&atilde;i v&agrave; chơi c&oacute; tr&aacute;ch nhiệm.</p>
<p>Ch&uacute;ng t&ocirc;i <strong>kh&ocirc;ng</strong> vận h&agrave;nh s&ograve;ng bạc hay nh&agrave; c&aacute; thể thao. Ch&uacute;ng t&ocirc;i kh&ocirc;ng đăng k&yacute; người chơi, kh&ocirc;ng giữ tiền v&agrave; kh&ocirc;ng trả thưởng. Chơi tiền thật chỉ diễn ra tr&ecirc;n c&aacute;c site b&ecirc;n thứ ba bạn chọn; ch&uacute;ng t&ocirc;i c&oacute; thể li&ecirc;n kết để tham khảo.</p>
<h2>Site d&agrave;nh cho ai</h2>
<p>Nội dung hướng tới người trưởng th&agrave;nh được ph&eacute;p đ&aacute;nh bạc hợp ph&aacute;p tại khu vực của họ. Nếu kh&ocirc;ng chắc, đừng d&ugrave;ng site cờ bạc.</p>
<h2>Độ ch&iacute;nh x&aacute;c</h2>
<p>Chúng t&ocirc;i cố giữ th&ocirc;ng tin cập nhật, nhưng khuyến m&atilde;i, điều khoản v&agrave; t&igrave;nh trạng sẵn c&oacute; thay đổi. Lu&ocirc;n x&aacute;c minh chi tiết quan trọng tr&ecirc;n site ch&iacute;nh thức của nh&agrave; vận h&agrave;nh trước khi nạp hoặc chơi.</p>
<h2>C&ocirc;ng bố li&ecirc;n kết</h2>
<p>Một số li&ecirc;n kết c&oacute; thể l&agrave; affiliate. Điều đ&oacute; c&oacute; thể nghĩa l&agrave; ch&uacute;ng t&ocirc;i nhận hoa hồng khi bạn đăng k&yacute; hoặc nạp &mdash; kh&ocirc;ng ph&iacute; th&ecirc;m cho bạn. Điều n&agrave;y kh&ocirc;ng ảnh hưởng độc lập bi&ecirc;n tập, nhưng quan hệ thương mại c&oacute; thể tồn tại.</p>
<h2>Li&ecirc;n hệ</h2>
<p>Để sửa th&ocirc;ng tin hoặc li&ecirc;n hệ kinh doanh, h&atilde;y d&ugrave;ng phương thức li&ecirc;n hệ tr&ecirc;n site (nếu c&oacute;).</p>
"""),
    "ua": _wrap("""
<h1>Про Ice Fish</h1>
<p><strong>Ice Fish</strong> &mdash; незалежний інформаційний ресурс про покрокову гру <em>Ice Fish</em> (InOut Games), ліцензовані казино, де вона доступна, а також демо, гайди, бонуси та відповідальну гру.</p>
<p>Ми <strong>не</strong> оператор казино чи букмекера. Ми не реєструємо гравців, не зберігаємо кошти та не виплачуємо виграші. Гра на гроші відбувається лише на сторонніх сайтах, які ви обираєте; ми можемо посилатися на них довідково.</p>
<h2>Для кого цей сайт</h2>
<p>Контент для дорослих, яким у їхній юрисдикції дозволено грати. Якщо сумніваєтеся &mdash; не користуйтеся азартними сайтами.</p>
<h2>Точність</h2>
<p>Ми намагаємося тримати інформацію актуальною, але бонуси, умови та наявність змінюються. Завжди перевіряйте важливі деталі на офіційному сайті оператора перед депозитом або грою.</p>
<h2>Розкриття партнерства</h2>
<p>Деякі посилання можуть бути партнерськими. Це може означати комісію за реєстрацію або депозит &mdash; без додаткових витрат для вас. На редакційну незалежність це не впливає, але комерційні відносини можливі.</p>
<h2>Контакт</h2>
<p>Для виправлення фактів або ділових запитів використовуйте контакт, опублікований на сайті (якщо є).</p>
"""),
    "ro": _wrap("""
<h1>Despre Ice Fish</h1>
<p><strong>Ice Fish</strong> este o resurs&#259; independent&#259; de informa&#539;ii despre jocul pe pa&#537;i <em>Ice Fish</em> (InOut Games), cazinouri licen&#539;iate care &icirc;l g&#259;zduiesc &#537;i subiecte conexe &mdash; demo, ghiduri, bonusuri &#537;i joc responsabil.</p>
<p><strong>Nu</strong> oper&#259;m un cazinou sau o cas&#259; de pariuri. Nu &icirc;nregistr&#259;m juc&#259;tori, nu p&#259;str&#259;m fonduri &#537;i nu pl&#259;tim c&acirc;&#537;tiguri. Jocul pe bani reali are loc doar pe site-uri ter&#539;e pe care le alegi; putem face leg&#259;turi c&#259;tre ele ca referin&#539;&#259;.</p>
<h2>Cui se adreseaz&#259; site-ul</h2>
<p>Con&#539;inutul este destinat adul&#539;ilor care au voie legal s&#259; joace &icirc;n jurisdic&#539;ia lor. Dac&#259; nu e&#537;ti sigur, nu folosi site-uri de jocuri de noroc.</p>
<h2>Acurate&#539;e</h2>
<p>&Icirc;ncerc&#259;m s&#259; men&#539;inem informa&#539;iile actualizate, dar bonusurile, termenii &#537;i disponibilitatea se schimb&#259;. Verific&#259; &icirc;ntotdeauna detaliile importante pe site-ul oficial al operatorului &icirc;nainte de depunere sau joc.</p>
<h2>Dezv&#259;luire afiliere</h2>
<p>Unele linkuri pot fi de afiliere. Putem primi comision dac&#259; te &icirc;nregistrezi sau depui &mdash; f&#259;r&#259; cost suplimentar pentru tine. Asta nu afecteaz&#259; independen&#539;a editorial&#259;, dar pot exista rela&#539;ii comerciale.</p>
<h2>Contact</h2>
<p>Pentru corec&#539;ii factuale sau solicit&#259;ri comerciale, folose&#537;te metoda de contact publicat&#259; pe acest site (dac&#259; este disponibil&#259;).</p>
"""),
}


def get_content(code: str) -> str:
    if code not in LOCALE_CONTENT:
        raise KeyError(f"Unknown locale code: {code}")
    return LOCALE_CONTENT[code]
