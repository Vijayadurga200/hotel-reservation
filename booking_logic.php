
<?php

function calculateTravelTime($room1, $room2)
{
    $horizontal = abs(
        $room1['room_position'] -
        $room2['room_position']
    );

    $vertical = abs(
        $room1['floor_number'] -
        $room2['floor_number']
    ) * 2;

    return $horizontal + $vertical;
}

function calculateTotalTravelTime($rooms)
{
    if (count($rooms) <= 1) {
        return 0;
    }

    usort($rooms, function ($a, $b) {

        if (
            $a['floor_number'] ==
            $b['floor_number']
        ) {
            return
                $a['room_position']
                <=>
                $b['room_position'];
        }

        return
            $a['floor_number']
            <=>
            $b['floor_number'];
    });

    $firstRoom = $rooms[0];
    $lastRoom = $rooms[count($rooms) - 1];

    return calculateTravelTime(
        $firstRoom,
        $lastRoom
    );
}

function findOptimalRooms($pdo, $roomCount)
{
    $stmt = $pdo->query("
        SELECT *
        FROM rooms
        WHERE is_occupied = 0
        ORDER BY floor_number, room_position
    ");

    $availableRooms =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($availableRooms) < $roomCount) {

        return [
            'success' => false,
            'message' =>
                'Not enough rooms available.'
        ];
    }

    $roomsByFloor = [];

    foreach ($availableRooms as $room) {

        $floor = $room['floor_number'];

        if (!isset($roomsByFloor[$floor])) {
            $roomsByFloor[$floor] = [];
        }

        $roomsByFloor[$floor][] = $room;
    }

    $bestRooms = null;
    $bestTravelTime = PHP_INT_MAX;

    /*
     * SAME FLOOR PRIORITY
     */
    foreach ($roomsByFloor as $floorRooms) {

        if (count($floorRooms) >= $roomCount) {

            for (
                $i = 0;
                $i <= count($floorRooms) - $roomCount;
                $i++
            ) {

                $candidate =
                    array_slice(
                        $floorRooms,
                        $i,
                        $roomCount
                    );

                $travelTime =
                    calculateTotalTravelTime(
                        $candidate
                    );

                if (
                    $travelTime <
                    $bestTravelTime
                ) {
                    $bestTravelTime =
                        $travelTime;

                    $bestRooms =
                        $candidate;
                }
            }
        }
    }

    /*
     * If same floor rooms found
     */
    if ($bestRooms !== null) {

        return [
            'success' => true,
            'rooms' => $bestRooms,
            'travel_time' =>
                $bestTravelTime
        ];
    }

    /*
     * Cross floor booking
     */
    $bestRooms =
        array_slice(
            $availableRooms,
            0,
            $roomCount
        );

    $bestTravelTime =
        calculateTotalTravelTime(
            $bestRooms
        );

    for (
        $i = 0;
        $i <= count($availableRooms) - $roomCount;
        $i++
    ) {

        $candidate =
            array_slice(
                $availableRooms,
                $i,
                $roomCount
            );

        $travelTime =
            calculateTotalTravelTime(
                $candidate
            );

        if (
            $travelTime <
            $bestTravelTime
        ) {
            $bestTravelTime =
                $travelTime;

            $bestRooms =
                $candidate;
        }
    }

    return [
        'success' => true,
        'rooms' => $bestRooms,
        'travel_time' =>
            $bestTravelTime
    ];
}

function bookRooms(
    $pdo,
    $roomCount,
    $guestName
) {

    $result =
        findOptimalRooms(
            $pdo,
            $roomCount
        );

    if (!$result['success']) {
        return $result;
    }

    $roomNumbers = [];

    foreach (
        $result['rooms']
        as $room
    ) {

        $roomNumbers[] =
            $room['room_number'];

        $stmt =
            $pdo->prepare("
                UPDATE rooms
                SET
                    is_occupied = 1,
                    guest_name = ?
                WHERE id = ?
            ");

        $stmt->execute([
            $guestName,
            $room['id']
        ]);
    }

    $stmt =
        $pdo->prepare("
            INSERT INTO bookings
            (
                guest_name,
                room_count,
                rooms_booked,
                total_travel_time
            )
            VALUES (?, ?, ?, ?)
        ");

    $stmt->execute([
        $guestName,
        $roomCount,
        implode(
            ', ',
            $roomNumbers
        ),
        $result['travel_time']
    ]);

    return [
        'success' => true,
        'message' =>
            'Rooms Booked: ' .
            implode(
                ', ',
                $roomNumbers
            ) .
            ' | Travel Time: ' .
            $result['travel_time'] .
            ' minutes'
    ];
}