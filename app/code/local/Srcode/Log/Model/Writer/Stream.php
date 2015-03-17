<?php
/**
 * Log writer which to stop flooding the log
 *
 * @copyright Srcode (2015)
 * @author Jeroen Boersma <jeroen@srcode.nl>
 */

/**
 * @package Srcode_Log
 * @category Srcode
 */
class Srcode_Log_Model_Writer_Stream extends Zend_Log_Writer_Stream
{

    /**
     * @var string
     */
    protected $_lastMessage;

    /**
     * @var int
     */
    protected $_lastMessageCount;

    /**
     * Close the stream resource.
     *
     * @return void
     */
    public function shutdown()
    {
        try {
            $this->_writeRepeated();
        } catch(Zend_Log_Exception $e) {
            // No problem if it should fail, don't want to break the program
        }

        // Close the file
        parent::shutdown();
    }

    /**
     * Write how often last message is repeated
     *
     * @throws Zend_Log_Exception
     */
    protected function _writeRepeated()
    {
        if ($this->_lastMessageCount < 2) {
            // Do nothing
            return;
        }

        $log = new Zend_Log;

        $event = array(
            'timestamp' => date($log->getTimestampFormat()),
            'priorityName' => 'DEBUG',
            'priority' => Zend_Log::DEBUG,
            'message' => sprintf("Last message repeated %s times", $this->_lastMessageCount)
        );

        $line = $this->_formatter->format($event);

        if (false === @fwrite($this->_stream, $line)) {
            #require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception("Unable to write to stream");
        }
    }

    /**
     * Write a message to the log.
     *
     * @param  array  $event  event data
     * @return void
     * @throws Zend_Log_Exception
     */
    protected function _write($event)
    {
        $line = $this->_formatter->format($event);

        if ($this->_lastMessage === $line) {
            // Repeated message, update the counter and return
            $this->_lastMessageCount++;
            return;
        } else {

            // Check for repeated message
            $this->_writeRepeated();

            // Put last message here and reset counter
            $this->_lastMessage = $line;
            $this->_lastMessageCount = 1;
        }

        // Write log
        if (false === @fwrite($this->_stream, $line)) {
            #require_once 'Zend/Log/Exception.php';
            throw new Zend_Log_Exception("Unable to write to stream");
        }
    }

}