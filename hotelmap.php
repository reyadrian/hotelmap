<?php

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://apis.ihg.com/guest-api/v1/ihg/us/en/rates",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    
    // based on the request (filter: 2 adults, 1 room, check-in: September 20-22, 2019)
    CURLOPT_POSTFIELDS => "{\"hotelCode\":\"ADLAH\",\"adults\":2,\"children\":0,\"rateCode\":\"6CBARC\",\"showPointsRate\":true,\"rooms\":1,\"version\":\"1.2\",\"corporateId\":\"\",\"travelAgencyId\":\"99801505\",\"dateRange\":{\"start\":\"2019-09-20\",\"end\":\"2019-09-22\"},\"memberID\":null}",
    

    // based on the request header
    CURLOPT_HTTPHEADER => [
        "Accept: application/json, text/plain, */*",
        "Accept-Encoding: gzip, deflate, br",
        "Accept-Language: en-US,en;q=0.5",
        "Cache-Control: no-cache",
        "Connection: keep-alive",
        "Content-Length: 228",
        "Content-Type: application/json",
        "DNT: 1",
        "Host: apis.ihg.com",
        "IHG-Language: en-US",
        "Origin: https://www.ihg.com",
        "Referer: https://www.ihg.com/holidayinnexpress/hotels/us/en/find-hotels/hotel/rooms?qCiMy=82019&qCiD=20&qCoMy=82019&qCoD=22&qAdlt=1&qChld=0&qRms=1&qRtP=6CBARC&qIta=99801505&qSlH=ADLAH&qAkamaiCC=GB&qSrt=sBR&qBrs=re.ic.in.vn.cp.vx.hi.ex.rs.cv.sb.cw.ma.ul.ki.va&qAAR=6CBARC&qWch=0&qSmP=1&setPMCookies=true&qRad=30&qRdU=mi&srb_u=1&icdv=99801505",
        "TE: Trailers",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:67.0) Gecko/20100101 Firefox/67.0",
        "X-IHG-API-KEY: se9ym5iAzaW8pxfBjkmgbuGjJcr3Pj6Y",
        "X-IHG-MWS-API-Token: 58ce5a89-485a-40c8-abf4-cb70dba4229b",
        "cache-control: no-cache"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
// $error = curl_errno($curl);
$responseCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);
if ($err) {
    exit("Curl Error #:" . $err);
} else {
    // if status code is 200 and the response is in a json format
    if($responseCode == 200 && $result = json_decode($response, true)) {

        // Rate codes - which use to identify which rate codes does room belong to
        $rates = $result['rates'];

        // store the room data
        $data = [];

        // All room types and codes
        foreach($result['rooms'] as $room) {

            // no wheelchair accessibility
            if(!$room['wheelchairAccessible']) {

                if(empty($data) || 

                    // when `room description` is not yet available in the $data array as an array key
                    !isset($data[$room['description']]) ||

                    // Finding a unique room description with the loweset rate (avgNightlyRate); 
                    // *** BEST FLEXIBLE RATE
                    (isset($data[$room['description']]) && $room['charges']['avgNightlyRate'] < $data[$room['description']]['NightlyRate'])) {

                    // set the array key to `room description` value for unique room description
                    $data[$room['description']] = [
                        "RoomName" => $room['description'],
                        "CurrencyCode" => $room['currencyCode'],
                        "NightlyRate" => $room['charges']['avgNightlyRate'],
                        "TotalBeforeTax" => $room['charges']['price'],
                        "TotalAfterTax" => $room['charges']['priceTotal'],

                        // based on the rateCode in $rates
                        "CancellationTerms" => $rates[$room['rateCode']]['cancellationPolicy'],
                    ];
                }
            }
        }

        // reset the array keys to numericals
        // display JSON nicely on the source code
        echo json_encode(array_values($data), JSON_PRETTY_PRINT);
    }

    // error response
    else {
        die($response);
    }
}