<?php
// ini_set('display_errors', 1);

date_default_timezone_set('Asia/Tashkent');

$owners = explode('|', file_get_contents('data/owners.dat'));
$application = file_get_contents('data/porjectid.dat');
$description = file_get_contents('data/description.dat');
$vote_payment = intval(file_get_contents('data/vote_payment.dat'));
$ref_payment = intval(file_get_contents('data/ref_payment.dat'));
$yechish= intval(file_get_contents('data/minimal.dat'));

// data papkasiga kirib owners.dat faylni toping admin idsini yozing!
$token = "6136594790:AAEYGLqss7weSXEdfJK7WovTcOq6SndDips"; // bot tokeni
$userboti = "@fargana_open_budjet_bot"; // bot useri @ siz yozing
define('API_KEY', $token );
function bot($method, $datas = [])
{
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

$update = json_decode(file_get_contents('php://input'));
$query = $update->inline_query;
include 'Telegram.php';
include 'functions.php';


$tg = new Telegram([
	'token' => $token
]);

$updates = $tg->get_webhookUpdates(); 
$buttons = [
			
];
if ( in_array( $tg->get_chatId(), $owners ) )  {
	$buttons[] = [ '🗣 Ovozlar', '🏦 Murojaatlar' ];
	$buttons[] = [ '📝 Matn', '🗄 Loyiha' ];
	$buttons[] = [ '💴 Ovoz berish', '💶 Referal' ];
	$buttons[] = [ '👨‍👩‍👧 Foydalanuvchilar', '👨‍💻 Adminlar'];
}else{
	$buttons[] = [ '📲 Telefon raqamni yuborish',];
	$buttons[] = [ '💳 Hisobim', '🔄 Pul yechib olish', ];
	$buttons[] = [ '🔗 Referal' ];
}



$startMessage = function($message = ""){
    global $owners, $tg, $description;

    setUserConfig( $tg->get_chatId(), 'lastmessage', '/start' );

    if( empty( $message ) ){
    	$message = "{$description}\n\n<b>Ovoz berish uchun telefon raqamingizni yuboring.</b>\n\nNamuna: <em>919992543</em>";
		
    }

	$buttons = [
			
	];
	if ( in_array( $tg->get_chatId(), $owners ) )  {
		$buttons[] = [ '🗣 Ovozlar', '🏦 Murojaatlar' ];
		$buttons[] = [ '📝 Matn', '🗄 Loyiha' ];
		$buttons[] = [ '💴 Ovoz berish', '💶 Referal' ];
		$buttons[] = [ '✍️ Bildirishnoma' ,'🔰 Yechish'];
		$buttons[] = [ '📁 Excel' , '🗑 Tozalash'];
		$buttons[] = [ '👨‍👩‍👧 Foydalanuvchilar', '👨‍💻 Adminlar'];
	}else{
		$buttons[] = [ '📲 Telefon raqamni yuborish',];
		$buttons[] = [ '💳 Hisobim', '🔄 Pul yechib olish', ];
		$buttons[] = [ '🔗 Referal' ];
	}
	$tg->send_chatAction('typing')
		->set_replyKeyboard($buttons)
		->send_message( $message );
};

$apiValidatePhone = function( $phone ){
    global $owners, $tg, $application;
	$tg->set_replyKeyboard([
		['❌ Bekor qilish']
	]);
    $tg->send_message("Iltimos kuting...");
if (check_phonenumber($phone)) {
    	$message = "⚠️ Bu raqam avval ovoz berish uchun ishlatilgan";
    	$tg->send_chatAction('typing')->send_message( $message );
    	exit;
    }



    


	$data = captcha($application);
     
	if (!empty( $data['key'] ) && !empty( $data['img'] ) ) {
		
		setUserConfig( $tg->get_chatId(), 'phone', $phone );
		setUserConfig( $tg->get_chatId(), 'tokencap', $data['key'] );
		setUserConfig( $tg->get_chatId(), 'token_expire', time() );
		setUserConfig( $tg->get_chatId(), 'lastmessage', 'captchaget');
		$tg->set_replyKeyboard([
			['❌ Bekor qilish']
		]);

		$message = "🖼 Suratdagi misolni javobini yuboring:";
		$tg->send_chatAction('upload_photo')->send_photo($data['img'], $message);
		
	}else{
		$message = "⚠️ Opendudget saytida yuklama oshganligi sababli ulanishlarda xatolik yuz berdi. Iltimos keyinroq ovoz berishga qaytadan urinib ko'ring";
		$tg->send_chatAction('typing')->send_message( $message);
	}
	

	


};

$applicationMessage = function(){
    global $tg, $config;
    $applications = get_applications();
    $applications_count = count($applications);
    if ( $applications_count == 0 ) {
        $tg->send_chatAction('typing')->send_message( '❌ Murojaatlar mavjud emas' );
        exit(1);
    }

    $application = application( $applications[0], $applications_count);
    $pagination = getPagination($applications[0]['time'], 0, $applications_count, 'app');
    array_unshift($pagination , [
        [
            'text' => '✅ Bajarildi',
            'callback_data' => 'app_s='.$applications[0]['chat_id']
        ],
    ]);
    $tg->send_chatAction('typing')->set_inlineKeyboard($pagination)->send_message( $application );
};

$statusMessages = function(){
    global $tg, $config;
    
    $count_notifications = message_status('count');
    $status = (message_status() == 'on') ? '🟢' : '🔴';
    $tg->send_chatAction('typing')->set_inlineKeyboard([
        [
            [
                "text" => "🟢",
                "callback_data" => "status=on"
            ],
            [
                "text" => "🔄",
                "callback_data" => "status=check"
            ],
            [
                "text" => "🔴",
                "callback_data" => "status=off"
            ]
        ],
        [
            [
                "text" => "🗑 Tozalash",
                "callback_data" => "clear=true"
            ]
        ],
    ])->send_message( "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}");
    
};

if (! empty( $updates ) ) {
	if (!empty($updates['message']['chat']['id'])) {
		$tg->set_chatId( $updates['message']['chat']['id'] );
	}

	if( ! empty( $updates['message']['text'] ) || $updates['message']['text'] == "0" ){
		
		$text = $updates['message']['text'];

		if (!empty( $updates['message']['chat']['first_name'] )){
			setUserConfig( $tg->get_chatId(), 'first_name', $updates['message']['chat']['first_name'] );
		}else{
			setUserConfig( $tg->get_chatId(), 'first_name', '');
		}
		if (!empty( $updates['message']['chat']['last_name'] )){
			setUserConfig( $tg->get_chatId(), 'last_name', $updates['message']['chat']['last_name'] );
		}else{
			setUserConfig( $tg->get_chatId(), 'last_name', '');
		}
		if (!empty( $updates['message']['chat']['username'] )){
			setUserConfig( $tg->get_chatId(), 'username', $updates['message']['chat']['username'] );
		}else{
			setUserConfig( $tg->get_chatId(), 'username', '');
		}

 		setUserConfig( $tg->get_chatId(), 'lastaction', time() );

 		if (preg_match('/\/start (\d+)/', $text, $refmatches)) {
			if ($refmatches[1] != $tg->get_chatId() && !file_exists('referals/'.$tg->get_chatId())) {
				

				$ref = getUserConfig( $refmatches[1], 'referals');
				if (empty($ref)) $ref = "0";
				setUserConfig( $refmatches[1], 'referals', strval( intval( $ref ) + 1 ) );
				

				setUserConfig( $tg->get_chatId(), 'refsum', 'berilamagan');
				file_put_contents('referals/'.$tg->get_chatId(), $refmatches[1]);
				$tg->send_chatAction('typing', $refmatches[1])->send_message( "ℹ️ Sizda yangi referal mavjud\nreferal do'stingiz ovoz bersa $ref_payment-so'm olasiz!", $refmatches[1] );
			}
			$startMessage();
		}else if ($text == '/start' || $text == '/asosiy') {
			$startMessage();
			$tg->send_message("<b>Aziz foydalanuvchi siz oʻz ovozingizni berish orqali botdan  paynet sohibi boʼlishiz mumkin.\nUnutmang sizning ovozingiz bizning mahallamiz obodonlashtirish uchun juda muhim.</b>");
		$tg->send_message("<b>Ovoz berish uchun telefon raqamingizni kiriting</b>");
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🔙 Orqaga' ) {
			$startMessage("👉 Asosiy menyu");
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '👨‍💻 Adminlar' ) {
            $owners_count = count($owners);
			if ( $owners_count == 0 ) {
				$tg->send_chatAction('typing')->send_message( '⚠️ Adminlar mavjud emas' );
				exit(1);
			}
			$user = json_decode( file_get_contents( 'users/'.$owners[0].'.json' ), TRUE );
			$user['id'] = $owners[0];
			$owner = owner($user , $owners_count);
			$pagination = getPagination($owners[0], 0, $owners_count, 'owner');
			array_unshift($pagination , [
                [
		            'text' => '➕ Qo‘shish',
		            'callback_data' => 'addowner=yes'
		        ],
		        [
		            'text' => '🗑 O‘chirish',
		            'callback_data' => 'removeowner='.$user['id']
		        ],
            ]);
			$tg->send_chatAction('typing')->set_inlineKeyboard($pagination)->send_message( $owner );
        }else if (in_array( $tg->get_chatId(), $owners ) && $text == '✍️ Bildirishnoma' ) {
            setUserConfig($tg->get_chatId(), 'lastmessage', 'send_notification');
            $tg->send_chatAction('typing')->set_replyKeyboard([
                ['🔙 Orqaga'],
            ])->send_message("📢 Foydalanuvchilarga bildirishnoma yuborish uchun quyida xabarni kiriting...");
        }else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            if ( strlen( $text ) > 10) {
				$message = $updates['message'];
				$messageid = $updates['message']['message_id'];
				$chatid = $updates['message']['chat']['id'];
				
 setUserConfig($tg->get_chatId(), 'lastmessage', 'xechnarsa');
    $users = glob('users/*.json');
	$counts = 0;
    foreach ($users as $user) {
        $fileName = basename($user);
        $chat_id = str_replace('.json', '', $fileName);
		bot('copyMessage',[
				'from_chat_id'=>$chatid,
			 'chat_id'=>$chat_id,
				'message_id'=>$messageid,
						   ]);
						   $counts++;
}
        
            

					$startMessage("✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni tugatildi yuborildi " . $counts . "-ta");
exit;


}else{
                $tg->send_chatAction('typing')->send_message( "<em>🛑 Kechirasiz, bildirishnoma matni 10 dona belgidan kam bo'lmasligi lozim.</em>" );   
            }
        }else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'clear_notification' ) {
            if ($text == '👍 Ha') {
                clear_notification();
                $startMessage("✅ Jarayondagi bildirishnomalar muvaffaqiyatli tozalandi.");
            }else{
                $startMessage("Asosiy menyu 👇");
            }
        }else if (in_array( $tg->get_chatId(), $owners ) && $text == '📁 Excel' ) {
			//$tg->send_message(get_url());
			$tg->send_message( "Excel faylni yuklash uchun shuyerga bosing:\n".get_url().'excel.php' );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🗑 Tozalash' ) {
			$tg->send_chatAction('typing')->set_inlineKeyboard([
        		[
            		[
               	 		"text" => "✅ Tozalash",
                		"callback_data" => "clearvote=yes"
            		]
        		]
    		])->send_message( "Siz chindan ham ovozlarni tozalamoqchimisiz?" );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '👨‍👩‍👧 Foydalanuvchilar' ) {
			$users = get_users();
			$users_count = count($users);
			if ( $users_count == 0 ) {
				$tg->send_chatAction('typing')->send_message( '⚠️ Foydalanuvchilar mavjud emas' );
				exit(1);
			}
			$user = user( $users[0], $users_count);
			$pagination = getPagination($users[0]['id'], 0, $users_count, 'users');
			$tg->send_chatAction('typing')->set_inlineKeyboard($pagination)->send_message( $user );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🗣 Ovozlar' ) {
			$votes = get_votes();
			$votes_count = count($votes);
			if ( $votes_count == 0 ) {
				$tg->send_chatAction('typing')->send_message( '⚠️ Ovozlar mavjud emas' );
				exit(1);
			}
			$vote = vote( $votes[0], $votes_count);
			$pagination = getPagination($votes[0]['time'], 0, $votes_count, 'votes');
			$tg->send_chatAction('typing')->set_inlineKeyboard($pagination)->send_message( $vote );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🏦 Murojaatlar' ) {
			$applicationMessage();
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🗄 Loyiha' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'porjectid' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message( "🆔 Iltimos loyiha idenfikatori kiriting\nHavola|(slka) emas!!!\n\n👉 Joriy idenfikator: " . $application );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '🔰 Yechish' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'minimum_summa' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message( "Minimal Summa yechish uchun summa kiriting faqat sonlarda:\n\n👉 Joriy summa: " . $yechish .'-so\'m');
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '📝 Matn' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'description' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message( "💬 Iltimos loyiha tavsifini kiriting\n\n👉 Joriy matn: " . $description );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '💴 Ovoz berish' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'vote_payment' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message( "💴 Iltimos har bir ovoz summasini kiriting\n\n👉 Joriy summa: " . $vote_payment );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '💶 Referal' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'ref_payment' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message( "💴 Iltimos har bir referal summasini kiriting\n\n👉 Joriy summa: " . $ref_payment );
		}else if (in_array( $tg->get_chatId(), $owners ) && $text == '👨‍👩‍👧 Malumot' ) {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'user_info' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['🔙 Orqaga']
			])->send_message("Foydalanuvchi idsini kiriting!?");
		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'porjectid' ) {
			file_put_contents('data/porjectid.dat', $text);
			$startMessage("ℹ️ Ma'lumot muvaffaqiyatli yangilandi");
		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'minimum_summa' ) {

               if (is_numeric($text)	) {
				file_put_contents('data/minimal.dat', $text);
			$startMessage("ℹ️ Ma'lumot muvaffaqiyatli yangilandi");
			   }else {
				$startMessage("Xato faqat sonlarda");
			   }	
			

		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'user_info' ) {
			

		// $te = userinfo($tg->get_chatId());
			$startMessage(" salomlar");
		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'description' ) {
			file_put_contents('data/description.dat', $text);
			$startMessage("ℹ️ Ma'lumot muvaffaqiyatli yangilandi");
		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'vote_payment' ) {
			file_put_contents('data/vote_payment.dat', strval( $text ));
			$startMessage("ℹ️ Ma'lumot muvaffaqiyatli yangilandi");
		}else if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'ref_payment' ) {
			file_put_contents('data/ref_payment.dat', strval( $text ));
			$startMessage("ℹ️ Ma'lumot muvaffaqiyatli yangilandi");
		}else  if (in_array( $tg->get_chatId(), $owners ) && getUserConfig( $tg->get_chatId(), 'lastmessage') == 'addowner' ) {
			$id = clear_phone($text);
			$owners[] = $id;
			file_put_contents('data/owners.dat', implode("|", $owners));
			$startMessage("ℹ️ Admin muvaffaqiyatli qo'shildi");
		}else if ($text == '/bekor' || $text == '❌ Bekor qilish') {
			$startMessage("ℹ️ Jarayon bekor qilindi");
		}else if ($text == '/hisobim' || $text == '💳 Hisobim') {
			$uc = getUserConfig( $tg->get_chatId(), 'balance');
			if (empty($uc)) $uc = "0";

			$tg->send_chatAction('typing')->send_message( "💰 Hisobda <b>{$uc} so'm</b> mavjud" );
		}else if ($text == '/uc_yechish' || $text == '🔄 Pul yechib olish') {
			setUserConfig( $tg->get_chatId(), 'lastmessage', 'exchange' );
			$tg->send_chatAction('typing')->set_replyKeyboard([
				['❌ Bekor qilish']
			])->send_message( "👉 <b>Pul</b> yechib olish uchun iltimos <b>Telefon yoki Karta </b> raqamni kiriting.\n\n<em>ℹ️ Minimal pul yechish miqdori: $yechish so'm</em>" );
		}else if ($text == '/referal' || $text == '🔗 Referal') {
			$ref = getUserConfig( $tg->get_chatId(), 'referals');
			if (empty($ref)) $ref = "0";
//ok
			$inlinek[] = ["text"=>"↗️ Doʻstlarga yuborish","switch_inline_query"=>""];
			$tg->send_chatAction('typing')->set_inlineKeyboard([
        		[
            		[
						
					"text"=>"↗️ Doʻstlarga yuborish"
				    ,"switch_inline_query"=>""
					
					]
        		]
    		])->send_message( "ℹ️ Referal manzil orqali do'stlaringizni botga taklif qiling va \"pul\" ishlab toping. Har bir referal uchun {$ref_payment} so'mdan taqdim etiladi.\n\n👨‍👩‍👦Referal orqali qo'shilganlar: {$ref} dona \n\nSizning referal manzilingiz 👇\n\nhttps://t.me/$userboti?start=".$tg->get_chatId()  );
		}else if ($text == '/haqida' || $text == '🤖 Bot haqida') {
			$tg->send_chatAction('typing')->send_message( "@adhambec"  );
		}else if ($text == '/new' || $text == '📲 Telefon raqamni yuborish') {
			$tg->send_chatAction('typing')->send_message( "Ovoz berish uchun telefon raqamingizni kiriting:"  );
	// $tg->send_chatAction('typing')->send_message( "Iltimos ovoz berish hali boshlanmadi tez orada boshlanadi!"  );
		}else if( getUserConfig( $tg->get_chatId(), 'lastmessage') == 'exchange' ){
			$uc = getUserConfig( $tg->get_chatId(), 'balance');
			if (empty($uc)) $uc = 0;
			if ( intval( $uc ) < $yechish ) {
				$startMessage("⚠️ Kechirasiz, ayriboshlash uchun hisob yetarli emas.\n\n<em>ℹ️ Minimal pul yechish miqdori: $yechish so'm</em>");
				exit();
			}
//ok
			$status = addRequest([
                'chat_id' => $tg->get_chatId(),
                'time' => time(),
                'text' => clear_phone( $text )
            ]);
            if ($status) {
            	$startMessage("✅ Pul yechib olish uchun so'rov muvaffaqiyatli yuborildi");
            }else{
            	$startMessage("⏳ Kechirasiz sizda avvalroq yuborilgan so'rov mavjud. Iltimos, jarayon yakunlanishini kuting.");
            }
		}else if( getUserConfig( $tg->get_chatId(), 'lastmessage') == 'captchaget' ){

			$tg->send_message("Iltimos kuting...");
			$phone = getUserConfig( $tg->get_chatId(), 'phone');
			$token = getUserConfig( $tg->get_chatId(), 'tokencap');
			if(is_numeric($text)){
			$get = getcode($token,$text,$phone,$application);

if($get['http_code']==200){
$tg->send_chatAction('typing')->send_message('kod yuborildi kiriting 60 soniya voqtingiz bor!');
setUserConfig($tg->get_chatId(),'lastmessage','otpkeylogin');
setUserConfig($tg->get_chatId(),'otpkey',$get['otpKey']);
}else{
setUserConfig($tg->get_chatId(),'lastmessage','xechnarsa');
$tg->send_chatAction('typing')->set_replyKeyboard($buttons)->send_message($get['message']."\n\nQayta urunib ko'ring!");

}

			


			}else{
				$tg->send_chatAction('typing')->send_message( "Faqat raqamlarda kiriting!");
			}
           
		
		}else if( getUserConfig( $tg->get_chatId(), 'lastmessage') == 'otpkeylogin' ){
			
			$phone = getUserConfig( $tg->get_chatId(), 'phone');
			$token = getUserConfig( $tg->get_chatId(), 'otpkey');
			$token_expire = intval( getUserConfig( $tg->get_chatId(), 'token_expire'));
			
			if ( $token_expire > ( time() -  60) ) {
				$data = varifycod($application,$text,$token);
				

				if ($data['http_code'] == 200) {
					$uc = getUserConfig( $tg->get_chatId(), 'balance');
					if (empty($uc)) $uc = "0";
					$newbalance = strval( intval( $uc ) + $vote_payment );
					setUserConfig( $tg->get_chatId(), 'balance',  $newbalance);

					$votes = getUserConfig( $tg->get_chatId(), 'votes');
					if (empty($votes)) $votes = "0";
					setUserConfig( $tg->get_chatId(), 'votes', strval( intval( $votes ) + 1 ) );

					add_vote([
						'time' => time(),
						'chat_id' => $tg->get_chatId(),
						'phone' => $phone
					]);

$refstat = getUserConfig($tg->get_chatId(), 'refsum');

if ($refstat=='berildi') {
	
}else{
$idref = file_get_contents('referals/'.$tg->get_chatId());
$tg->send_chatAction('typing', $idref)->send_message( "ℹ️ Referal do'stingiz ovoz berdi va $ref_payment-so'm oldingiz!", $idref );

$uc = getUserConfig($idref, 'balance');
if (empty($uc)) $uc = "0";
setUserConfig( $idref, 'balance', strval(intval($uc) + $ref_payment ) );


setUserConfig( $tg->get_chatId(), 'refsum', 'berildi' );
}



		



					$startMessage("✅ Ovoz qabul qilindi.\nHisobdagi mablag': <b>{$newbalance} so'm</b>\n\n👉 Ovoz berib pul ishlashda davom etish uchun telefon raqam kiring...");
				}else if ($data['http_code'] == 400) {
					$tg->send_chatAction('typing')->send_message( "❌ Tasdiqlash kodi xato kiritildi" );
				}else{
				$startMessage("❌ Opendudget saytida yuklama oshganligi sababli ulanishlarda xatolik yuz berdi. Iltimos keyinroq ovoz berishga qaytadan urinib ko'ring" . $approximate_time);
				}
			}else{
				$startMessage("🚫 Tasdiqlash kodini kiritish vaqti tugagan. Iltimos qaytadan so'rov yuboring");
			}
			//$startMessage("✅ Loyihaga ovoz berganingiz uchun rahmat");
		}else if ( preg_match('/^[+]?998/', $text) || strlen( $text ) == 9 ) {
			if (strlen( $text ) == 9) {
				$text = "998".$text;
			}

			if ( validate_phone( clear_phone( $text ) ) ) {
			
				$apiValidatePhone( clear_phone( $text ) );
			
			}else{
				$tg->send_chatAction('typing')->send_message( "⚠️ Kechirasiz telefon raqam formati mos emas yoki raqam O'zbekiston hududidan tashqarida" );
			}
		
		}else{
			$tg->send_chatAction('typing')->send_message( "Kechirasiz men sizni tushuna olmadim 🤷‍♂️" );
		}
	}

	if( ! empty( $updates['message']['contact'] ) ){
		$phone = clear_phone( $updates['message']['contact']['phone_number'] );
		if ( validate_phone( $phone ) ) {
			
			$apiValidatePhone( $phone );
		
		}else{
			$tg->send_chatAction('typing')->send_message( "⚠️ Kechirasiz telefon raqam formati mos emas yoki raqam O'zbekiston hududidan tashqarida" );
		}
	}







	if( ! empty( $updates['message']['photo'] ) ){
        if (in_array( $tg->get_chatId(), $owners ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            $photo = end($updates['message']['photo']);
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            add_notifications([
                'photo' => $photo['file_id'],
                'caption' => $caption
            ]);
            $startMessage("✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi");
        }
    }








    if( ! empty( $updates['message']['video'] ) ){
        if (in_array( $tg->get_chatId(), $owners ) && getUserConfig($tg->get_chatId(), 'lastmessage') == 'send_notification' ) {
            $video = $updates['message']['video']['file_id'];
            $caption = (!empty($updates['message']['caption'])) ? $updates['message']['caption'] : '';
            add_notifications([
                'video' => $video,
                'caption' => $caption
            ]);
            $startMessage("✅ Foydalanuvchilarga bildirishnoma yuborish jarayoni boshlandi");
        }
    }

	if( ! empty( $updates['callback_query']['data'] ) ){
		$tg->set_chatId($updates['callback_query']['message']['chat']['id']);
		parse_str($updates['callback_query']['data'], $query);
		if (count($query) > 0) {

			if ( ! empty( $query['status'] ) ) {
                if (in_array($query['status'], ['on', 'off'])) {
                    message_status($query['status']);
                    $count_notifications = message_status('count');
                    $status = (message_status() == 'on') ? '🟢' : '🔴';
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => $updates['callback_query']['message']['reply_markup'],
                        'text' => "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}",
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Holat o'zgartirildi"]);
                }elseif ($query['status'] == 'check') {
                    $count_notifications = message_status('count');
                    $status = (message_status() == 'on') ? '🟢' : '🔴';
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => $updates['callback_query']['message']['reply_markup'],
                        'text' => "Bildirishnoma yuborish holati: {$status}\n\n⏳ Jarayondagi xabarlar: {$count_notifications}",
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "⚠️ Tizimda xatolik yuzberdi", 'show_alert' => true]);
                }
            }
            if ( ! empty( $query['clear'] ) ) {
                if ($query['clear'] == 'true') {
                    setUserConfig($tg->get_chatId(), 'lastmessage', 'clear_notification');
                    $tg->send_chatAction('typing')->set_replyKeyboard([
                        ['👍 Ha', '🙅‍♂️ Yo‘q'],
                        ['🔙 Orqaga'],
                    ])->send_message("⚠️ Siz chindan ham jarayondagi bildirishnomalarni o'chirmoqchimisiz?");
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Variantlardan birini tanlang"]);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "⚠️ Tizimda xatolik yuzberdi", 'show_alert' => true]);
                }
            }

            if ( ! empty( $query['addowner'] ) ) {
                setUserConfig($tg->get_chatId(), 'lastmessage', 'addowner');
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
                $tg->send_chatAction('typing')->set_replyKeyboard([
                    ['🔙 Orqaga'],
                ])->send_message("🆔 Admin qo'shish uchun idenfikator kiriting");
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Ma'lumotni kiriting"]);
            }

            if ( ! empty( $query['removeowner'] ) ) {
                $id = $query['removeowner'];
                $temp_owners = [];
                foreach ($owners as $owner) {
                	if ($owner != $id) {
                		$temp_owners[] = $owner;
                	}
                }
                file_put_contents('data/owners.dat', implode("|", $temp_owners));
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Admin o'chirildi"]);
                $startMessage("✅ Admin o'chirildi");
            }

            if ( ! empty( $query['clearvote'] ) ) {
            	clear_votes();
                $tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
                $tg->send_chatAction('typing')->send_message("Ma'lumotlar o'chirildi");
                $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Ma'lumotlar tozalandi"]);
            }

            if ( ! empty( $query['owner'] ) ) {
				$owners_count = count($owners);
				if ( $owners_count == 0 ) {
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "❌ Foydalanuvchilar mavjud emas"]);
					exit(1);
				}
				$page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
				$owner = array_slice($owners, $page, 1, true);
                if (count($owner) > 0) {
                	$owner = reset($owner);
                	$user = json_decode( file_get_contents( 'users/'.$owner.'.json' ), TRUE );
					$user['id'] = $owner;
					$message = owner($user , $owners_count);
					$pagination = getPagination($owner, $page, $owners_count, 'owner');
					array_unshift($pagination , [
		                [
				            'text' => '➕ Qo‘shish',
				            'callback_data' => 'addowner=yes'
				        ],
				        [
				            'text' => '🗑 O‘chirish',
				            'callback_data' => 'removeowner='.$user['id']
				        ],
		            ]);
					$req = $tg->request('editMessageText', [
                    	'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                        	'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                	$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natijalar topilmadi"]);
                }
			}


			if ( ! empty( $query['users'] ) ) {
				$users = get_users();
				$users_count = count($users);
				if ( $users_count == 0 ) {
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "❌ Foydalanuvchilar mavjud emas"]);
					exit(1);
				}
				$page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
				$user = array_slice($users, $page, 1, true);
                if (count($user) > 0) {
                	$user = reset($user);
                	
                	$message = user( $user, $users_count);
					$pagination = getPagination($user['id'], $page, $users_count, 'users');
					$req = $tg->request('editMessageText', [
                    	'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                        	'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                	$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natijalar topilmadi"]);
                }
			}

			if ( ! empty( $query['votes'] ) ) {
				$votes = get_votes();
				$votes_count = count($votes);
				if ( $votes_count == 0 ) {
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "❌ Foydalanuvchilar mavjud emas"]);
					exit(1);
				}
				$page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
				$vote = array_slice($votes, $page, 1, true);
                if (count($vote) > 0) {
                	$vote = reset($vote);
                	
                	$message = vote( $vote, $votes_count);
					$pagination = getPagination($vote['time'], $page, $votes_count, 'votes');
					$req = $tg->request('editMessageText', [
                    	'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                        	'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
					$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natija yangilandi"]);
                }else{
                	$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "Natijalar topilmadi"]);
                }
			}

			if ( ! empty( $query['app'] ) ) {
                $applications = get_applications();
                $applications_count = count($applications);
                if ( $applications_count == 0 ) {
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => '❌ Murojaatlar mavjud emas']);
                    exit(1);
                }
                $page = ( array_key_exists('prev', $query) ) ? intval($query['prev']) : intval($query['next']);
                $application = array_slice($applications, $page, 1, true);
                if (count($application) > 0) {
                    $application = reset($application);
                    
                    $message = application( $application, $applications_count);
                    $pagination = getPagination($application['chat_id'], $page, $applications_count, 'app');
                    array_unshift($pagination , [
                        [
				            'text' => '✅ Bajarildi',
				            'callback_data' => 'app_s='.$application['chat_id']
				        ],
                    ]);
                    $req = $tg->request('editMessageText', [
                        'chat_id' => $updates['callback_query']['message']['chat']['id'],
                        'message_id' => $updates['callback_query']['message']['message_id'],
                        'reply_markup' => [
                            'inline_keyboard' => $pagination
                        ],
                        'text' => $message,
                        'parse_mode' => 'html',
                        'disable_web_page_preview' => true
                    ]);
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natija yangilandi']);
                }else{
                    $tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => 'Natijalar topilmadi']);
                }
            }

            if ( ! empty( $query['app_s'] ) ) {
            	setUserConfig( $query['app_s'], 'balance', "0" );
            	@unlink('requests/' . $query['app_s'].'.json');
            	$tg->send_message( "✅ Pul ayriboshlash muvaffaqiyatli amalga oshirildi", $query['app_s'] );
            	$tg->request('answerCallbackQuery', ['callback_query_id' => $updates['callback_query']['id'], 'text' => "✅ Harakat muvaffaqiyatli bajarildi", 'show_alert' => true]);
            	$tg->request('deleteMessage', ['chat_id' => $updates['callback_query']['message']['chat']['id'], 'message_id' => $updates['callback_query']['message']['message_id']]);
            	$applicationMessage();
            }
		}
	}
}