#!/bin/sh
# vim:ft=sh:ts=2:sts=2:sw=2:et:
set -o nounset
set -o pipefail

CURL=/usr/bin/curl
CFGFILE=`mktemp -t ftp_list.cfg.XXXXXXX`
OUTFILE=`mktemp -t ftp_list.out.XXXXXXX`
chmod 600 ${CFGFILE} ${OUTFILE}

writeConfig() {
  echo "user = \"${ftp_username}:${ftp_password}\"" > ${CFGFILE}
}

finish() {
  [ -f ${CFGFILE} ] && rm -f "${CFGFILE}"
  [ -f ${OUTFILE} ] && rm -f "${OUTFILE}"
}
trap finish EXIT

if [ ! -e "${ftp_local_file}" ]; then
  echo "Cannot find backup file ${ftp_local_file} to upload"
  ls -la ${ftp_local_path}
  df -h
  exit 11
fi

writeConfig

# Ensure ftp_path ends with a slash
if [ `echo $ftp_path | grep -c '/$'` -eq 0 ]; then
  ftp_path="${ftp_path}/"
fi

${CURL} \
  --config ${CFGFILE} \
  --ftp-ssl \
  --insecure \
  --silent \
  --show-error \
  --ftp-create-dirs \
  --upload-file ${ftp_local_file} \
  ftp://$ftp_ip:${ftp_port:-21}$ftp_path$ftp_remote_file > ${OUTFILE} 2>&1

RET=$?

if [ "${RET}" -ne 0 ]; then
  echo "${CURL} returned error code ${RET}";
  cat ${OUTFILE}
  exit ${RET}
fi
