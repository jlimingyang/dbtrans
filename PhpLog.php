<?php
/**
 * 定义写log的类
 * @author sparkqiao
 *
 */
class PhpLogger
{
    const DEBUG_LEVEL = 1;
    const INFO_LEVEL = 2;
    const WARNING_LEVEL = 3;
    const ERROR_LEVEL = 4;
    const LOGFILE_MAXSIZE = 104857600;//100M
    //日志文件所在路径
    private $m_logpath;
    //日志文件名
    private $m_logname;
    private $m_logfile;
    private $m_bInit;
    private $m_loglevel;
    function __construct()
    {
        $this->m_logpath = NULL;
        //日志文件名
        $this->m_logname = NULL;
        $this->m_filesize = 0;
        $this->m_lckfile = NULL;
        $this->m_logfile = NULL;
        $this->m_bInit = FALSE;
        $this->m_loglevel = self::DEBUG_LEVEL;
        
    }
    public function Init($fpath,$fname)
    {
        if($this->m_bInit)
            return;
        if(!isset($fpath) || trim($fpath) =='')
            $this->m_logpath = getcwd().'\\log';
        else
            $this->m_logpath = trim($fpath);
        if(!isset($fname) || trim($fname) =='')
            $this->m_logname = "log.txt";
        else
            $this->m_logname = $fname;
        date_default_timezone_set('PRC');
        $this->m_logfile = $this->m_logpath . '\\'. $this->m_logname . '.' . date('Y_m_d_G');
        $this->m_bInit = TRUE;
     }
    public function Loger($level,$file,$fline,$str)
    {
        if($level < $this->m_loglevel)
            return;
        if($this->m_bInit == false)
            return ;
        if(!file_exists($this->m_logfile))
        {
                // touch($this->m_logfile);
        }
        if(!is_file($this->m_logfile))
        {
            return;
        }
        if(filesize($this->m_logfile) > self::LOGFILE_MAXSIZE)
        {
            date_default_timezone_set('PRC');
            $this->m_logfile = $this->m_logpath . '\\'. $this->m_logname . '.' . date('Y_m_d_G');
        }

        $curtime = gettimeofday(true);
        
        $fp = fopen($this->m_logfile,"a");
        if($fp != false)
        {
            if(flock($fp, LOCK_EX) == true)
            {
                fprintf($fp,"time:[%s]%s\n",date("m-d H:i:s"), $file . ':' . $fline . ',' . trim($str));
                flock($fp,LOCK_UN);
            }
            fclose($fp);            
        }
        
    }
}
?>