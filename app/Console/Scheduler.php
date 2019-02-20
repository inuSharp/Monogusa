<?php


/*

* * * * * root cd /vagrant/monogusa/Monogusa;php console scheduler >> /dev/null 2>&1
every:1
every:5
every:10
every:15
every:20
hourly:00 毎時00分
hourly:30 毎時30分
dayly:1000


*/
$schedules = [
//    'sample,every:1',
    'sample2,every:5',
    'sample3,every:10',
];

execScheduler($schedules);

