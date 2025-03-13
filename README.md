# PicCachePro
## by Markus Jäger
### Version 1.0.1

---

Dieses Framework konvertiert eine Rastergrafik, die über ihren vollständigen Serverpfad ausgewählt wird, in das AVIF-Format. Dabei kannst du sowohl die Bildqualität als auch die Zielbreite des AVIF-Bildes nach deinen Wünschen einstellen.

Als Zielbreite wird entweder die Quellbreite verwendet oder, falls du eine andere Breite angibst, wird das Bild auf die nächste Hunderterstelle herunterskaliert, sofern diese kleiner als die Quellbreite ist.

Um die Leistung zu optimieren, wird ein zusätzlicher AVIF-Cache verwendet. Vor der Erstellung des AVIF-Bildes überprüft das Framework, ob bereits ein Bild mit den angegebenen Parametern im Cache vorhanden ist. Falls ja, wird das AVIF-Bild aus dem Cache verwendet. Andernfalls wird das AVIF-Bild neu erstellt und anschließend im Cache gespeichert, um den Zugriff bei zukünftigen Anfragen zu beschleunigen.

---

### Low-level framework without dependencies:
```
PicCachePro-main
```

---

#### Version 1.0.1:
- Optimized code quality.

#### Version 1.0:
- Optimized code quality.

#### Version 0.99:
- Optimized code quality.

#### Version 0.98:
- Optimized code quality.
- Added Protected methods and properties for late static binding.

#### Version 0.97:
- Optimized code quality.

#### Version 0.96:
- Convert avifCreateRawFromPath to Callback function.

#### Version 0.95:
- Bugfix: Prevent a PHP error if the server does not support imagemagick AVIF.
- Comments updated.

#### Version 0.94:
- Bugfix: avifCreateRawFromPath.

#### Version 0.93:
- Round the image width.

#### Version 0.92:
- Code Update.

#### Version 0.91:
- Code Update.

#### Version 0.9:
- Init.