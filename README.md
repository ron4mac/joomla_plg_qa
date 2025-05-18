# joomla_plg_qa
A simple, yet effective, Joomla! captcha plugin that uses a question and answer method.  

What is the 4<sup>th</sup> letter of the word 'foxtail'? ... possible answers: t,T  
How many 3's are in the number: 1,347,237? ... possible answers: 2,two  
What is the sum of 2 plus three? ... possible answers: 5,five  

The plugin will also reject rapid submissions of the form.  

Captcha labeling and questions are customizable ... see file <em>custom.ini.sample</em> in the language folder.

#### Version 1.5+ moves questions and answers to JSON files in <em>media/plg_captcha_qa/qalang/</em>
This allows any number of custom Q&A's. See <em>media/plg_captcha_qa/qalang/custom/qandas_custom.json</em>.
