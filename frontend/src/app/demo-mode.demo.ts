export const DEMO_MODE = true;

const services = [
  { id: 'exam', naziv: 'Stomatološki pregled', trajanje: 30, cena: 2500 },
  { id: 'cleaning', naziv: 'Čišćenje zuba', trajanje: 45, cena: 4000 },
  { id: 'consult', naziv: 'Ortodoncijska konsultacija', trajanje: 30, cena: 3000 },
];

const teams = [
  { id: 'a', naziv: 'Demo tim A', vodja: 'Fiktivni stomatolog A', clanova: 2 },
  { id: 'b', naziv: 'Demo tim B', vodja: 'Fiktivni stomatolog B', clanova: 2 },
];
const booked = new Set<string>();
const times = ['09:00', '10:30', '13:00', '15:30'];

function dates() {
  const today = new Date();
  return Array.from({length: 10}, (_, i) => {
    const day = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i + 1);
    return [day.getFullYear(), String(day.getMonth() + 1).padStart(2, '0'), String(day.getDate()).padStart(2, '0')].join('-');
  });
}

export async function bookingDemo(url: string, options: RequestInit) {
  const {pathname, searchParams} = new URL(url, 'http://demo.invalid');
  const route = '/api/patient/booking';
  if (pathname === route + '/services') return { services };
  if (pathname === route + '/teams') return { teams };
  if (pathname === route + '/days') return { days: dates() };
  if (pathname === route + '/slots') {
    const key = searchParams.get('team_id') + ':' + searchParams.get('date') + ':';
    return { slots: times.filter(time => !booked.has(key + time)) };
  }
  if (pathname === route && options.method === 'POST') {
    const data = JSON.parse(String(options.body || '{}'));
    const key = [data.team_id, data.date, data.time].join(':');
    if (!services.some(s => s.id === data.service_id) || !teams.some(t => t.id === data.team_id)
      || !dates().includes(data.date) || !times.includes(data.time) || booked.has(key)) {
      throw { status: 422, error: { message: 'Izaberite slobodan termin za probnu rezervaciju.' } };
    }
    booked.add(key);
    return { message: 'Probni termin je zakazan. Podaci nisu poslati klinici.' };
  }
  throw { status: 403, error: { message: 'Ova radnja nije dostupna u demonstraciji.' } };
}
