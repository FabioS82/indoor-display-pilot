
// Update this page (the content is just a fallback if you fail to update the page)

const Index = () => {
  // Redirect to the main HTML page
  window.location.href = '/index.html';
  
  return (
    <div className="min-h-screen flex items-center justify-center bg-background">
      <div className="text-center">
        <h1 className="text-4xl font-bold mb-4">Redirecionando...</h1>
        <p className="text-xl text-muted-foreground">Carregando painel Digital Signage...</p>
      </div>
    </div>
  );
};

export default Index;
