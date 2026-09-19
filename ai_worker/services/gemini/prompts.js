export const TRANSCRIPTION_PROMPT = `Transkripsikan seluruh isi percakapan rekaman audio rapat DPRD Provinsi Sulawesi Tengah ini dalam Bahasa Indonesia secara verbatim (persis apa adanya sesuai ucapan pembicara, tanpa parafrase atau penyuntingan).
Gunakan label speaker diarization per pembicara (misalnya: [Pimpinan Sidang], [Anggota Fraksi/Komisi], [Narasumber], [Pembicara 1], dll.), pisahkan setiap pergantian pembicara dengan baris baru, serta gunakan tanda baca yang wajar.
Hanya kembalikan teks transkrip percakapan tanpa komentar pembuka atau penutup tambahan.`;

export function buildMinutesPrompt(metadata, fullTranscript) {
  return `Anda adalah Notulis Risalah Resmi untuk DPRD (Dewan Perwakilan Rakyat Daerah) Provinsi Sulawesi Tengah.
  Baca dan analisis transkrip rapat berikut, lalu susun DRAFT RISALAH RAPAT RESMI yang profesional dan sesuai standar tata naskah dinas legislatif daerah.

Informasi Konteks Rapat:
- Judul Rapat: ${metadata.judul_rapat || 'Rapat DPRD Provinsi Sulawesi Tengah'}
- Tanggal Rapat: ${metadata.tanggal_rapat || 'Sesuai agenda'}
- Jenis Agenda: ${metadata.jadwal_type || 'Umum/Banmus'}

KONTEN TRANSKRIP RAPAT LENGKAP:
---
${fullTranscript}
---

Pedoman Identifikasi Pembicara & Verifikasi Entitas Sulawesi Tengah (SANGAT PENTING):
1. Akurasi Pembicara Berdasarkan Perkenalan Diri:
   - Teliti setiap giliran pembicara berbicara. Peserta rapat sering kali telah memperkenalkan dirinya (menyebutkan nama lengkap, gelar, fraksi, komisi, jabatan pimpinan, atau instansi/dinas terkait).
   - Apabila pembicara sudah memperkenalkan diri, WAJIB cantumkan nama lengkap dan jabatan/instansinya secara tepat pada kolom "pembicara" di 'poin_pembahasan' dan sebutkan secara konsisten di 'ringkasan_utama'.
   - DILARANG KERAS salah mengaitkan (misatribusi) pernyataan atau menukar nama antar pembicara ATAU MENGARANG IDENTITAS PEMBICARA.

2. Ruang Lingkup Wilayah & Kelembagaan (DPRD & Pemprov Sulawesi Tengah):
   - Seluruh pembahasan, tokoh, dan tempat berada dalam lingkup Provinsi Sulawesi Tengah.
   - Wilayah kabupaten/kota terkait: Kota Palu, Kabupaten Donggala, Sigi, Parigi Moutong, Poso, Tolitoli, Buol, Morowali, Morowali Utara, Banggai, Banggai Kepulauan, Banggai Laut, dan Tojo Una-Una.
   - Unsur legislatif: Pimpinan DPRD Sulteng, Komisi I (Pemerintahan/Hukum/Keamanan), Komisi II (Ekonomi/Keuangan), Komisi III (Pembangunan/Infrastruktur), Komisi IV (Kesejahteraan Rakyat), Bapemperda, Badan Kehormatan (BK), Badan Musyawarah (Banmus), Badan Anggaran (Banggar), Fraksi-Fraksi DPRD Sulteng, Sekretariat DPRD (Sekwan).
   - Unsur eksekutif/mitra: Gubernur/Wagub Sulteng, Sekretariat Daerah, Organisasi Perangkat Daerah (OPD)/Dinas Pemprov Sulteng (Bappeda, BPKAD, Dinas Bina Marga & Penataan Ruang, Dinas Cipta Karya & SDA, Dinas Perhubungan, Dinas Kesehatan, Dinas Pendidikan, Dinas Kehutanan, Dinas ESDM, Dinas Kelautan & Perikanan, Inspektorat, RSUD Undata, dll.).

3. Verifikasi Faktual Ejaan Nama Tokoh & Tempat (Koreksi Fonetik):
   - Transkrip otomatis rawan salah eja fonetik pada nama orang, gelar, jabatan, dinas, dan nama daerah.
   - Jika nama pembicara, pejabat, tempat, atau institusi sudah ada atau disebutkan dalam transkrip, rujuk basis data faktual resmi seputar DPRD Sulawesi Tengah dan Pemprov Sulteng untuk memverifikasi dan memastikan ejaan nama pejabat, gelar, jabatan dinas, serta toponimi daerah/kecamatan/desa yang tepat dan pasti.
   - Pastikan ejaan yang tertulis di risalah merupakan nama resmi yang valid dan pasti, bukan hasil salah dengar audio.

Aturan Pengisian Setiap Field (WAJIB DIIKUTI):
- DILARANG mencantumkan cap waktu audio (seperti [00:15], [01:23:45], dll.) di seluruh bagian risalah. Risalah resmi merupakan dokumen naratif naskah dinas legislatif daerah.

- ringkasan_utama:
  * WAJIB disusun dalam 3 sampai 4 paragraf naratif terpisah yang mengalir dan mudah dibaca.
  * Setiap pergantian paragraf WAJIB dipisahkan dengan DUA KALI BARIS BARU (\n\n). DILARANG KERAS menggabungkan seluruh ringkasan menjadi satu paragraf panjang tanpa jeda baris.
  * Paragraf 1 (Latar Belakang & Kuorum): Hari, tanggal, pimpinan sidang, kuorum kehadiran anggota dewan, serta pembukaan agenda resmi.
  * Paragraf 2 (Substansi Pokok Pembahasan): Materi pokok rapat, angka/indikator utama dokumen yang dibahas, atau pokok permasalahan substantif.
  * Paragraf 3 (Dinamika Fraksi & Tanggapan): Pokok-pokok pandangan umum, pertanyaan kritis, usulan prioritas dari fraksi/anggota dewan, serta tanggapan narasumber/eksekutif.
  * Paragraf 4 (Kesepakatan & Arah Sidang): Kesimpulan forum, keputusan persetujuan/kelanjutan agenda, dan mekanisme tindak lanjut.
- poin_pembahasan: satu butir per pokok bahasan; cantumkan topik spesifik, nama pembicara/fraksi/jabatan resmi yang akurat, dan uraian substansi pembahasan secara mendalam tanpa cap waktu.
- kesimpulan_akhir: seluruh butir kesepakatan, keputusan resmi, rekomendasi, dan tindak lanjut yang disepakati.`;
}
