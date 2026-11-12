// Bewertungen, die nicht über das Formular kamen: von der alten Website und von Shops übernommen.
// Wortlaut unverändert. Neue Bewertungen kommen über das Formular in die Datenbank
// und werden nach der Freigabe in der Konsole automatisch auf der Seite angezeigt.
//
// quelle 'website' = auf unserer eigenen Seite abgegeben. Nur diese dürfen in die
// Google-Bewertungsdaten (Schema.org), Shop-Bewertungen nicht.

export type Quelle = 'website' | 'amazon' | 'thalia';

export interface Bewertung {
  name: string;
  datum: string; // JJJJ-MM-TT
  sterne: 1 | 2 | 3 | 4 | 5;
  titel: string;
  text: string;
  quelle: Quelle;
  verifiziert?: boolean; // „Verifizierter Kauf“ bei Amazon
}

export const quellen: Record<Quelle, { name: string; url?: string }> = {
  website: { name: 'groeppel-und-picky.de' },
  amazon: { name: 'Amazon', url: 'https://www.amazon.de/product-reviews/3752627654/' },
  thalia: { name: 'Thalia', url: 'https://www.thalia.de/shop/home/artikeldetails/A1059767215' },
};

// Die Amazon-Bewertung von „Matze“ (14.12.2020) hat fast denselben Wortlaut wie die von Miriam
// und wird deshalb bewusst nicht gezeigt.
export const bewertungen: Bewertung[] = [
  {
    name: 'Paula', datum: '2022-09-16', sterne: 5, quelle: 'website',
    titel: 'Warten auf den zweiten Teil',
    text: 'Wir haben das Buch schon so oft gelesen und es ist jedesmal wieder so eine schöne Geschichte. Unsere Tochter malt selbst schon Gröppel und Picky und fragt nun nach einer Fortsetzung. Wir hoffen, daß es einen zweiten Teil gibt!',
  },
  {
    name: 'Christian', datum: '2021-02-10', sterne: 5, quelle: 'amazon', verifiziert: true,
    titel: 'Wundervolles Kinderbuch',
    text: 'Ein ganz tolles Kinderbuch! Super lustig und für meinen siebenjährigen Sohn sehr gut zu verstehen. Wir hatten sehr viel Freude und freuen uns schon auf eine Fortsetzung!',
  },
  {
    name: 'Martin', datum: '2021-01-08', sterne: 5, quelle: 'website',
    titel: 'Ein buntes Abenteuer. Toll',
    text: 'Ich habe das Buch zusammen mit meinem 5 jährigen Sohn gelesen. Wir fanden es richtig toll und sind gespannt auf eine Fortsetzung. Das Buch ist für Kinder ab 4 Jahren, zum Vorlesen, und für Erstleser sehr zu empfehlen.',
  },
  {
    name: 'Ohne Namen', datum: '2020-12-16', sterne: 5, quelle: 'thalia',
    titel: 'Spannendes und lustiges Abenteuer',
    text: 'Wir haben das das Buch durch eine Empfehlung von Freunden gekauft. Die Geschichte ist spannend und lustig geschrieben. Ein toller Einfall die beiden Hauptfiguren durch die Zeit zu schicken und sie Gutes tun zu lassen. Unsere Kinder sind 6 und 9 Jahre alt. Beiden hat es sehr gut gefallen. Ein absoluter Geheimtipp!',
  },
  {
    name: 'Miriam', datum: '2020-12-11', sterne: 5, quelle: 'website',
    titel: 'Begeistert!',
    text: 'Mein 7 jähriger Sohn ist total begeistert von dem Buch. So toll geschrieben mit so schönen Bildern. Wir warten jetzt schon ungeduldig auf den zweiten Teil.',
  },
];

// Für Schema.org: nur die auf unserer Seite abgegebenen Bewertungen
export const ldBewertungen = (liste: Bewertung[] = bewertungen) => {
  const eigene = liste.filter(b => b.quelle === 'website');
  if (!eigene.length) return {};
  const schnitt = eigene.reduce((s, b) => s + b.sterne, 0) / eigene.length;
  return {
    aggregateRating: { '@type': 'AggregateRating', ratingValue: schnitt.toFixed(1), bestRating: 5, worstRating: 1, ratingCount: eigene.length, reviewCount: eigene.length },
    review: eigene.map(b => ({
      '@type': 'Review', name: b.titel, reviewBody: b.text, datePublished: b.datum,
      author: { '@type': 'Person', name: b.name },
      reviewRating: { '@type': 'Rating', ratingValue: b.sterne, bestRating: 5, worstRating: 1 },
    })),
  };
};
