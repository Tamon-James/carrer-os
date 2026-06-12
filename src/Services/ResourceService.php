<?php
declare(strict_types=1);

final class ResourceService {
  private const ALLOWED = [
    'pdf'=>['application/pdf'],
    'doc'=>['application/msword','application/CDFV2','application/octet-stream'],
    'docx'=>['application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/zip'],
    'txt'=>['text/plain'],
  ];

  public function storeUpload(int $uid, array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('ファイルを選択してください。');
    if ((int)$file['size'] > APP_MAX_UPLOAD_BYTES) throw new RuntimeException('ファイルは10MB以下にしてください。');
    $original=basename(str_replace('\\','/',(string)$file['name']));
    $extension=strtolower(pathinfo($original,PATHINFO_EXTENSION));
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name']);
    if (!isset(self::ALLOWED[$extension]) || !in_array($mime,self::ALLOWED[$extension],true)) throw new RuntimeException('PDF、Word、テキストのみアップロードできます。');
    $dir=(string)config_value('upload_dir',private_root().'/uploads').DIRECTORY_SEPARATOR.$uid;
    if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new RuntimeException('保存先を作成できません。');
    $stored=bin2hex(random_bytes(20)).'.'.$extension;
    if (!move_uploaded_file((string)$file['tmp_name'],$dir.DIRECTORY_SEPARATOR.$stored)) throw new RuntimeException('ファイルを保存できません。');
    return ['original_name'=>$original,'stored_name'=>$stored,'mime_type'=>$mime,'size_bytes'=>(int)$file['size']];
  }

  public function absolutePath(int $uid,string $storedName): string {
    return (string)config_value('upload_dir',private_root().'/uploads').DIRECTORY_SEPARATOR.$uid.DIRECTORY_SEPARATOR.basename($storedName);
  }
}
