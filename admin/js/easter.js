/*global dotclear */
'use strict';

dotclear.easter = () => {
  let hue = 0;

  const particlesArray = [];
  const canvas = document.getElementById('canvas-layer');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  class Particle {
    constructor(x, y) {
      this.x = x;
      this.y = y;
      this.size = Math.random() * 8;
      this.speedX = Math.random() * 3 - 1.5;
      this.speedY = Math.random() * 3 - 1.5;
      this.color = `hsl(${hue}, 100%, 50%)`;
    }

    update() {
      this.x += this.speedX;
      this.y += this.speedY;
    }

    draw() {
      ctx.fillStyle = this.color;
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
      ctx.fill();
    }
  }

  const animate = () => {
    ctx.fillStyle = 'rgba(58, 83, 155, 1)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    hue += 2;
    handleParticles();
    requestAnimationFrame(animate);
  };

  function createParticles(x, y) {
    for (let i = 0; i < 2; i++) {
      particlesArray.push(new Particle(x, y));
    }
  }

  const handleParticles = () => {
    particlesArray.forEach((particle) => {
      if (particle.x > canvas.width || particle.x < 0) particle.x = Math.random() * canvas.width;
      if (particle.y > canvas.height || particle.y < 0) particle.y = Math.random() * canvas.height;
      particle.update();
      particle.draw();
    });
  };

  const moveParticlesTo = (destX, destY) => {
    particlesArray.forEach((particle) => {
      particle.x = destX;
      particle.y = destY;
    });
  };

  const controller = new AbortController();

  canvas.addEventListener(
    'mousemove',
    (event) => {
      createParticles(event.x, event.y);
    },
    { signal: controller.signal },
  );

  canvas.addEventListener(
    'click',
    (event) => {
      moveParticlesTo(event.x, event.y);
    },
    { signal: controller.signal },
  );

  canvas.addEventListener('dblclick', (event) => {
    controller.abort();
    canvas.width = 0;
    canvas.height = 0;
  });

  createParticles();
  animate();
};
