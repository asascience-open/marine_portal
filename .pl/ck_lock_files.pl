#!/usr/bin/perl

use strict;
use DBI;

if (-e '/tmp/lock_ck_lock_files') {
  exit;
}

`touch /tmp/lock_ck_lock_files`;

my $tol_min = 2;
my $db_name = $ENV{dbName};
my $db_user = $ENV{dbUser};
my $db_pass = $ENV{dbPass};
my $db_port = $ENV{dbPort};

my $dbh  = DBI->connect("DBI:Pg:dbname=$db_name;port=$db_port",$db_user,$db_pass);
my $now  = time();
my $host = `hostname`;
chomp($host);

my %d;
my $sth = $dbh->prepare('select n,t,alerted from lock_files order by n,t');
$sth->execute();
while (my @row = $sth->fetchrow_array()) {
  $d{$row[0]} = {
     't'       => $row[1]
    ,'alerted' => $row[2]
  };
}

opendir(DIR,'/tmp');
while (my $f = readdir(DIR)) {
  if ($f =~ /^lock_/) {
    my ($dev,$ino,$mode,$nlink,$uid,$gid,$rdev,$size,$atime,$mtime,$ctime,$blksize,$blocks)
      = stat("/tmp/$f");
    print STDERR "$f\n";
    if ($mtime > 0 && $now - $mtime > $tol_min * 60) {
      my $t_str = scalar(localtime($mtime));
      if (defined($d{$f}) && $d{$f}{'t'} eq $t_str) {
        if ($d{$f}{'alerted'} == 0) {
          $dbh->do("update lock_files set alerted = true where n = '$f'");
          print STDERR "\talert!\n";
          my $cmd = "echo 'You might want to login to the server to investigate.' | mail -s '$f has been running for more than $tol_min minutes on $host' 'charlton\@2creek.com'";
          print `$cmd`;
        }
      }
      else {
        $dbh->do("insert into lock_files (n,t) values ('$f','$t_str')");
      }
    }
    elsif (defined($d{$f}) && $d{$f}{'alerted'} == 1) {
      print STDERR "\tresumed!\n";
      my $cmd = "echo '' | mail -s '$f has recovered on $host' 'charlton\@2creek.com'";
      print `$cmd`;
      $dbh->do("delete from lock_files where n = '$f'");
    }
  }
}
closedir(DIR);

$dbh->disconnect();

`rm -f /tmp/lock_ck_lock_files`;
