<?php
class CalendarController
{
    public function view()
    {
        return render('calendar', []);
    }

    public function get()
    {
        $events = [];
        for ($i=1; $i<30; $i++) {
            $events[] = [
                "event_id"   => $i,
                "day"   => $i,
                "title" => 'イベント' . $i,
                "type"  => 'blue', // blue red green
            ];
        }

        $data = [
            'year' => 2019,
            'month'=> 1,
            'event'=> $events,
            'holiday' => ['3', '4', '5']
        ];
        return responseJson($data, 200);
    }
}
