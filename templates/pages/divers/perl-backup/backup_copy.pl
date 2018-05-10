#!/usr/bin/perl
use Time::Local;
use File::Copy;

my $Erreur = "";
my $DELETE_FOLDERS = 1;
my $LOGFILE = "";
my $SRC = "";
my $DST = "";

if(@ARGV < 2) {
	$Erreur = "Not enough arguments";
}

if(@ARGV > 4 ) { 
	$Erreur = "Too much arguments";
}
if(@ARGV >= 2 ) { 
	$SRC = $ARGV[0];
	$DST = $ARGV[1];
}
if(@ARGV >= 3 ) { 
	if( lc($ARGV[2]) eq "0"     ) { $DELETE_FOLDERS = 0; }
	if( lc($ARGV[2]) eq "no"    ) { $DELETE_FOLDERS = 0; }
	if( lc($ARGV[2]) eq "non"   ) { $DELETE_FOLDERS = 0; }
	if( lc($ARGV[2]) eq "false" ) { $DELETE_FOLDERS = 0; }
}
if(@ARGV >= 4 ) { 
	$LOG_FILE = $ARGV[3];
}

if( $Erreur ne "" ) {
	print "Error: ".$Erreur."\n\n".
	             "Usage: perl backup_copy.pl <Source> <Dest> [Delete] [Log]\n".
	             "       - Source : source folder\n".
	             "       - Dest   : destination folder\n".
	             "       - Delete : whether to delete backup old files (default TRUE)\n".
	             "       - Log    : log file (default : no log)\n"
} else {
	if( $LOG_FILE ne "" ) { open FICLOG, '>'.$LOG_FILE;}
	$SRC =~ s/\\/\//g;
	$SRC =~ s/\/+$//g;
	$DST =~ s/\\/\//g;
	$DST =~ s/\/+$//g;
	backup($SRC, $DST);
	if( $LOG_FILE ne "" ) { close FICLOG; }
}

sub backup {
	my $SRC = shift;
	my $DST = shift;
	if(-e $SRC) {
		#create folder
		if(!(-e $DST) ) {
			mkdir $DST;
			logwrite("Create Folder ".$DST);
		}
		#copy
		my @files = getFiles($SRC);
		foreach my $file(@files) {
			if(-e $DST.'/'.$file) {
				if(fileupdated($SRC.'/'.$file, $DST.'/'.$file)) {
					logwrite("Copy File     ".$DST.'/'.$file);
					copy($SRC.'/'.$file, $DST.'/'.$file);
				}
			}
			else {
				logwrite("Copy File     ".$DST.'/'.$file);
				copy($SRC.'/'.$file, $DST.'/'.$file);
			}
		}
		my @folders = getFolders($SRC);
		foreach my $folder(@folders) {
			backup($SRC.'/'.$folder, $DST.'/'.$folder);
		}
		#delete
		if( $DELETE_FOLDERS ) {
			@files = getFiles($DST);
			foreach my $file(@files) {
				if(!(-e $SRC.'/'.$file)) {
					logwrite("Delete File   ".$DST.'/'.$file);
					unlink($DST.'/'.$file);
				}
			}
			@folders = getFolders($DST);
			foreach my $folder(@folders) {
				if(!(-e $SRC.'/'.$folder)) {
					logwrite("Delete Folder ".$DST.'/'.$folder);
					cleanfolder($DST.'/'.$folder);
					rmdir($DST.'/'.$folder);
				}
			}
		}
	}
}

sub cleanfolder {
	my $src = shift;
	my @files = getFiles($src);
	foreach my $file(@files) {
		unlink($src.'/'.$file);
	}
	my @folders = getFolders($src);
	foreach my $folder(@folders) {
		cleanfolder($src.'/'.$folder);
		rmdir($src.'/'.$folder);
	}
}

sub logwrite {
	my $line = shift;
	my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	my $datenow = sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year+1900, $mon+1, $mday, $hour, $min,$sec);
	if( $LOG_FILE ne "" ) { print FICLOG $datenow."  ".$line."\n"; }
}

sub fileupdated {
	my $src = shift;
	my $dst = shift;
	my $stat_src = stat($src);
	my $stat_dst = stat($dst);
	if($stat_src[7] != $stat_dst[7] || $stat_src[9] > $stat_dst[9]) {
		return 1;
	}
	return 0;
}

sub getFiles {
	my $folder = shift;
	opendir(REPSCAN, $folder) || die "can't opendir $folder: $!";
	my @files = grep { -f $folder.'/'.$_ || -l $folder.'/'.$_ } readdir(REPSCAN);
	closedir REPSCAN;
	return @files;
}

sub getFolders {
	my $folder = shift;
	opendir(REPSCAN, $folder) || die "can't opendir $folder: $!";
	my @dirs = grep { !/^\.\.?$/ && -d $folder.'/'.$_ } readdir(REPSCAN);
	closedir REPSCAN;
	return @dirs;
}