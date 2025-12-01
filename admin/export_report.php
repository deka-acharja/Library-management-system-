<?php
include('../includes/db.php');

$type   = $_GET['type'] ?? '';
$month  = $_GET['month'] ?? date('m');
$year   = $_GET['year']  ?? date('Y');
$sd = "$year-" . str_pad($month,2,'0',STR_PAD_LEFT) . "-01";
$ed = date("Y-m-t", strtotime($sd));

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="'.$type.'-'.date('Y-m').'.csv"');

$out = fopen('php://output','w');

function queryToCSV($conn, $sql, $params, $header) {
  global $out;
  fputcsv($out, $header);
  $stmt = $conn->prepare($sql);
  $types = str_repeat('s', count($params));
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_row()) {
    fputcsv($out, $r);
  }
}

switch($type) {
  case 'issued':
    queryToCSV($conn,
      "SELECT 'reservation' AS source, reservation_id, name, email, title, author, serial_no, reservation_date, status
       FROM reservations WHERE status IN('taken','borrowed') AND reservation_date BETWEEN ? AND ?
       UNION ALL
       SELECT 'borrowed', b.borrow_id, u.name, u.email, bo.title, bo.author, bo.serialno, b.taken_date, b.status
       FROM borrow_books b JOIN users u ON b.cid=u.username JOIN books bo ON b.book_id=bo.id
       WHERE b.status IN('taken','borrowed') AND b.taken_date BETWEEN ? AND ?",
       [$sd, $ed, $sd, $ed],
      ['Source','ID','Name','Email','Title','Author','Serial','Date','Status']
    );
    break;
  case 'overdue':
    queryToCSV($conn,
      "SELECT 'reservation', reservation_id, name, email, title, author, serial_no, reservation_date, status
       FROM reservations WHERE status='due' AND reservation_date BETWEEN ? AND ?
       UNION ALL
       SELECT 'borrowed', b.borrow_id, u.name, u.email, bo.title, bo.author, bo.serialno, b.taken_date, b.status
       FROM borrow_books b JOIN users u ON b.cid=u.username JOIN books bo ON b.book_id=bo.id
       WHERE b.status='due' AND b.taken_date BETWEEN ? AND ?",
       [$sd,$ed,$sd,$ed],
      ['Source','ID','Name','Email','Title','Author','Serial','Date','Status']
    );
    break;
  case 'returned':
    queryToCSV($conn,
      "SELECT b.borrow_id, u.name, u.email, bo.title, bo.author, bo.serialno, b.return_date, b.status
       FROM borrow_books b JOIN users u ON b.cid=u.username JOIN books bo ON b.book_id=bo.id
       WHERE b.status='returned' AND b.return_date BETWEEN ? AND ?",
       [$sd,$ed],
      ['ID','Name','Email','Title','Author','Serial','Date','Status']
    );
    break;
  case 'fines':
    queryToCSV($conn,
      "SELECT name, cid, title, days_overdue, amount, payment_date FROM payments WHERE payment_date BETWEEN ? AND ?
       UNION ALL
       SELECT u.name, u.username, b.title, DATEDIFF(pb.payment_date, bb.return_due_date), pb.fine_amount, pb.payment_date
       FROM payment_borrowers pb JOIN borrow_books bb ON pb.borrow_id=bb.borrow_id
         JOIN users u ON bb.cid=u.username JOIN books b ON bb.book_id=b.id
       WHERE pb.payment_date BETWEEN ? AND ?",
       [$sd,$ed,$sd,$ed],
      ['Name','CID','Title','OverdueDays','Amount','PaymentDate']
    );
    break;
  case 'users':
    queryToCSV($conn,
      "SELECT name, username, email, role, created_at FROM users WHERE created_at BETWEEN ? AND ?",
       [$sd,$ed],
      ['Name','Username','Email','Role','CreatedAt']
    );
    break;
  default:
    fputcsv($out, ['Invalid report type']);
}

fclose($out);
exit;
?>
