<?php
/**
 * Telegram Notifications for PAPACS System.
 *
 * @author Pvt Ferdinand P Lazarte (Inf) PA
 * @botname PAPACS
 * @bot_username papacs_bot
 */

 defined('MOODLE_INTERNAL') || die();

 require_once($CFG->dirroot.'/message/output/lib.php');
 require_once($CFG->dirroot.'/lib/filelib.php');

 require_once(__DIR__ . 'TelegramBotPHP/telegram.php');

 //Telegram Credentials
 define('TELEGRAM_TOKEN', '6163000440:AAEKIj0yxnPOkSV-dlOg_Uol977ULAmS1AQ');
 define('CHAT_ID', '-703429874');


 class papacs_telegram extends message_output {

    /**
     * Processes the message and sends a notification via telegram
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;
        $telegram = new Telegram(TELEGRAM_TOKEN);
        $content = ['chat_id' => CHAT_ID, 'text' => $eventdata->fullmessage];
        return $telegram->sendMessage($content);
    }

 }