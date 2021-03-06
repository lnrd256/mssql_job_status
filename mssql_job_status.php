{\rtf1\ansi\ansicpg1252\deff0\deflang8202{\fonttbl{\f0\fswiss\fcharset0 Arial;}}
{\*\generator Msftedit 5.41.21.2508;}\viewkind4\uc1\pard\f0\fs20
<?php \par
$options= getopt("H:U:P:j:");\par
if (count($options)<4)\{\par
    print('-H HOST -U USER -P PASSWORD -j JOB \\n ');\par
    exit(3);\par
\}\par
if($options["P"]==NULL || $options["H"]==NULL || $options["j"]==NULL || $options["U"]==NULL)\{\par
    print('-H HOST -U USER -P PASSWORD -j JOB \\n ');\par
    exit(3);\par
\}\par
$server= $options["H"];\par
$user= $options["U"];\par
$passwd= $options["P"];\par
$job=$options["j"];\par
$link =mssql_connect($server,$user,$passwd);\par
\par
\par
$version = mssql_query("SELECT\par
\tab [sJOB].[job_id] AS [JobID]\par
\tab , [sJOB].[name] AS [JobName]\par
\tab , CASE\par
    \tab WHEN [sJOBH].[run_date] IS NULL OR [sJOBH].[run_time] IS NULL THEN NULL\par
    \tab ELSE CAST(\par
            \tab CAST([sJOBH].[run_date] AS CHAR(8))\par
            \tab + ' '\par
            \tab + STUFF(\par
                \tab STUFF(RIGHT('000000' + CAST([sJOBH].[run_time] AS VARCHAR(6)),  6)\par
                    \tab , 3, 0, ':')\par
                \tab , 6, 0, ':')\par
            \tab AS DATETIME)\par
  \tab END AS [LastRunDateTime]\par
\tab , CASE [sJOBH].[run_status]\par
    \tab WHEN 0 THEN 'Failed'\par
    \tab WHEN 1 THEN 'Succeeded'\par
    \tab WHEN 2 THEN 'Retry'\par
    \tab WHEN 3 THEN 'Canceled'\par
    \tab WHEN 4 THEN 'Running' -- In Progress\par
  \tab END AS [LastRunStatus],\par
    [sJOBH].[message] AS [LastRunStatusMessage]\par
    \par
FROM\par
\tab [msdb].[dbo].[sysjobs] AS [sJOB]\par
\tab LEFT JOIN (\par
            \tab SELECT\par
                \tab [job_id]\par
                \tab , MIN([next_run_date]) AS [NextRunDate]\par
                \tab , MIN([next_run_time]) AS [NextRunTime]\par
            \tab FROM [msdb].[dbo].[sysjobschedules]\par
            \tab GROUP BY [job_id]\par
        \tab ) AS [sJOBSCH]\par
    \tab ON [sJOB].[job_id] = [sJOBSCH].[job_id]\par
\tab LEFT JOIN (\par
            \tab SELECT\par
                \tab [job_id]\par
                \tab , [run_date]\par
                \tab , [run_time]\par
                \tab , [run_status]\par
                \tab , [run_duration]\par
                \tab , [message]\par
                \tab , ROW_NUMBER() OVER (\par
                                        \tab PARTITION BY [job_id]\par
                                        \tab ORDER BY [run_date] DESC, [run_time] DESC\par
                  \tab ) AS RowNumber\par
            \tab FROM [msdb].[dbo].[sysjobhistory]\par
            \tab WHERE [step_id] = 0\par
        \tab ) AS [sJOBH]\par
    \tab ON [sJOB].[job_id] = [sJOBH].[job_id]\par
    \tab AND [sJOBH].[RowNumber] = 1\par
WHERE [sJOB].[name]='$job'\par
");\par
$row = mssql_fetch_array($version);\par
if($row['LastRunStatus']=='Failed')\{\par
    $state='CRITICAL';\par
    $exit=2;\par
\}else if($row['LastRunStatus']=='Succeeded')\{\par
    $state='OK';\par
    $exit=0;\par
\}else \{\par
    $state='UNKNOWN';\par
    $exit=0;\par
\}\par
\par
fwrite(STDOUT,$state.' - '.$row['LastRunStatusMessage']);\par
mssql_free_result($version);\par
exit($exit);\par
?>\par
}
