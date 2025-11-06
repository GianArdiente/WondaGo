
<style>
  .swal2-container {
    z-index: 9999999 !important;
  }
  body {
    margin: 0;
    font-family: sans-serif;
    background: #fff;
  }
  .example-container {
    overflow: hidden;
    position: absolute;
    width: 100vw;
    height: 100vh;
  }

  #location-dropdowns {
    position: fixed;
    top: 1.5vw;
    left: 50%;
    transform: translateX(-50%);
    z-index: 100000;
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    padding: 2vw 4vw;
    display: flex;
    gap: 3vw;
    align-items: center;
    font-size: 1rem;
    max-width: 95vw;
    flex-wrap: wrap;
  }
  #location-dropdowns label {
    font-weight: 600;
    font-size: 1em;
  }
  #location-dropdowns select {
    margin-right: 1vw;
    padding: 0.5em 1em;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 1em;
    min-width: 100px;
    max-width: 40vw;
  }

  #ar-container {
    width: 100vw;
    height: 100vh;
    position: relative;
    overflow: hidden;
  }

  #example-scanning-overlay {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    opacity: 0.5;
    bottom: 0;
    background: transparent;
    z-index: 2;
  }
  @media (min-aspect-ratio: 1/1) {
    #example-scanning-overlay .inner {
      width: 50vh;
      height: 50vh;
    }
  }
  @media (max-aspect-ratio: 1/1) {
    #example-scanning-overlay .inner {
      width: 80vw;
      height: 80vw;
    }
  }

  #example-scanning-overlay .inner {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background:
      linear-gradient(to right, white 10px, transparent 10px) 0 0,
      linear-gradient(to right, white 10px, transparent 10px) 0 100%,
      linear-gradient(to left, white 10px, transparent 10px) 100% 0,
      linear-gradient(to left, white 10px, transparent 10px) 100% 100%,
      linear-gradient(to bottom, white 10px, transparent 10px) 0 0,
      linear-gradient(to bottom, white 10px, transparent 10px) 100% 0,
      linear-gradient(to top, white 10px, transparent 10px) 0 100%,
      linear-gradient(to top, white 10px, transparent 10px) 100% 100%;
    background-repeat: no-repeat;
    background-size: 40px 40px;
  }

  #example-scanning-overlay.hidden {
    display: none;
  }

  #example-scanning-overlay .inner img {
    opacity: 0.22; /* bawasan para hindi natatakpan ang AR trail — i-tweak kung kailangan */
    width: 90%;
    align-self: center;
    max-width: 100%;
    height: auto;
  }

  #example-scanning-overlay .inner .scanline {
    position: absolute;
    width: 100%;
    height: 8px;
    background: rgba(255,255,255,0.25); /* semi-transparent scanline */
    animation: move 2s linear infinite;
  }
  @keyframes move {
    0%, 100% { top: 0% }
    50% { top: calc(100% - 10px) }
  }

  /* Extra mobile responsiveness */
  @media (max-width: 600px) {
    #location-dropdowns {
      flex-direction: column;
      gap: 2vw;
      padding: 3vw 2vw;
      font-size: 0.95em;
      top: 2vw;
      left: 50%;
      max-width: 98vw;
    }
    #location-dropdowns select {
      min-width: 80vw;
      max-width: 98vw;
      margin-right: 0;
      margin-bottom: 1vw;
      font-size: 1em;
    }
    #ar-container {
      width: 100vw;
      height: 100vh;
    }
    #example-scanning-overlay .inner {
      width: 95vw !important;
      height: 95vw !important;
    }
  }
</style>