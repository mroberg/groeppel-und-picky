// Zentrale Daten: Links, Termine, Bücher. Hier ändern, nicht in den Seiten.

export const hoerbuchStart = '2026-11-15T00:00:00+01:00';

export const social = {
  facebook: 'https://facebook.com/GroeppelPicky',
  instagram: 'https://instagram.com/groeppelpicky',
};

export const band1 = {
  titel: 'Gröppel und Picky bei den Indianern',
  untertitel: 'Ein Zeitreiseabenteuer · Band 1',
  isbn: '978-3-75262-765-7',
  erschienen: '30.11.2020 bei BoD',
  alter: 'ab 6 Jahren, zum Vorlesen ab 4',
  buch: [
    { name: 'BoD', url: 'https://www.bod.de/buchshop/groeppel-und-picky-bei-den-indianern-matthias-roberg-9783752627657' },
    { name: 'Thalia', url: 'https://www.thalia.de/shop/home/artikeldetails/A1059767215' },
    { name: 'Amazon', url: 'https://www.amazon.de/dp/3752627654/' },
  ],
  ebook: [
    { name: 'Kindle', url: 'https://www.amazon.de/dp/B08QN3JW8X' },
    { name: 'Lehmanns', url: 'https://www.lehmanns.de/shop/kinderbuch-jugenbuch/55768081-9783752638059-groeppel-und-picky-bei-den-indianern' },
    { name: 'bol.com', url: 'https://www.bol.com/be/nl/p/groppel-und-picky-groppel-und-picky-bei-den-indianern-ebook/9300000020240196/' },
  ],
  hoerbuch: [
    { name: 'Spotify', url: 'https://open.spotify.com/show/12fxUnJWacBdZYzc6Da2KF' },
    { name: 'Audible', url: 'https://www.audible.de/pd/B0HPM1XHSM' },
    { name: 'Storytel', jetzt: true, url: 'https://www.storytel.com/de/books/gr%C3%B6ppel-und-picky-bei-den-indianern-ein-zeitreiseabenteuer-15539339' },
  ],
};

export const band2 = {
  titel: 'Gröppel und Picky und die Piraten',
  erscheint: 'Frühjahr 2027',
};

export const ausmalbilder = [
  { titel: 'Gröppel und Picky auf dem Skateboard', datei: 'Ausmalbild-Skateboard.pdf', vorschau: 'ausmalbild-skateboard.webp' },
  { titel: 'Gröppel und Picky als Piraten', datei: 'Ausmalbild-Pirat.pdf', vorschau: 'ausmalbild-pirat.webp' },
  { titel: 'Gröppel und Picky mit Elefanten', datei: 'Ausmalbild-Elefant.pdf', vorschau: 'ausmalbild-elefant.webp' },
  { titel: 'Gröppel und Picky und die Schlange', datei: 'Ausmalbild-Schlange.pdf', vorschau: 'ausmalbild-schlange.webp' },
];

export const kontakt = {
  name: 'Matthias Roberg',
  strasse: 'Im Siek 19',
  ort: '23845 Itzstedt',
  telefon: '+49 173 2319305',
  email: 'kontakt@groeppel-und-picky.de',
};

// Strukturierte Daten (schema.org) für Suchmaschinen
const SITE = 'https://groeppel-und-picky.de';
export const ldAutor = { '@type': 'Person', '@id': `${SITE}/ueber-uns/#matthias`, name: 'Matthias Roberg', url: `${SITE}/ueber-uns/` };
export const ldIllustrator = { '@type': 'Person', '@id': `${SITE}/ueber-uns/#ludwig`, name: 'Ludwig Tredup', url: `${SITE}/ueber-uns/` };
export const ldSerie = { '@type': 'BookSeries', '@id': `${SITE}/abenteuer/#serie`, name: 'Gröppel und Picky', url: `${SITE}/abenteuer/`, author: ldAutor };
export const ldWebsite = {
  '@type': 'WebSite', '@id': `${SITE}/#website`, name: 'Gröppel und Picky', url: `${SITE}/`, inLanguage: 'de',
  publisher: { ...ldAutor, sameAs: [social.facebook, social.instagram] },
};
