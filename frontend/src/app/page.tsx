import { getBackendApiUrl } from '@/shared/api/config';

export default function HomePage() {
  const backendApiUrl = getBackendApiUrl();

  return (
    <main>
      <section>
        <p>Assistant recettes</p>
        <h1>Préparation du parcours shopper</h1>
        <p>
          Le socle frontend est prêt pour connecter l'expérience de chat recettes au backend Symfony.
        </p>
        <p>API backend configurée : {backendApiUrl}</p>
      </section>
    </main>
  );
}
