{
MODE="Compress"

if (/adx|ahx|pmf|png|ADX|AHX|PMF|PNG|cri_w/) {
  MODE="Uncompress"
}
printf("%s, %s, %s, %s\n", $0, $0, "", MODE)
}