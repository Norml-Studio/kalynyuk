/**
 * Calculator — PORTED VERBATIM from the two inline <script> blocks that lived in
 * Divi code modules on page 566. Technical debt by decision (Petr, 2026-07-31),
 * matching sections/_calculator-legacy.scss.
 *
 * ⚠️ THE MATHS IS NOT TO BE "IMPROVED". It computes the annuity payment, the DSTI
 * ratio, Imposto do Selo and IMT for mainland and islands — Portuguese regulated
 * figures on the site of a licensed credit intermediary. Every formula below is
 * byte-identical to what was running in production. If something is wrong in it,
 * that is a question for Anna, not a refactor.
 *
 * TWO CHANGES were unavoidable in the move, both structural, neither touching logic:
 *
 *   1. Each block is wrapped in its own IIFE. As inline <script> tags they shared
 *      the global scope, and the two blocks declare several of the same names
 *      (`inputs`, `netIncomeInput`, `propertyPriceInput`, `n`…). Block 2 got away
 *      with it only because its whole body sat inside a DOMContentLoaded callback.
 *      Separate scopes make the collision structurally impossible.
 *   2. Block 2's `document.addEventListener("DOMContentLoaded", …)` became a plain
 *      IIFE. This module is called from main.js, which already runs on (or after)
 *      DOMContentLoaded — so that listener would have been registered too late and
 *      never fired, leaving the results panel frozen at 0 €.
 */

export function initCalculator() {
  /*
   * ⚠️ THE GUARD MUST NOT NAME A CLASS THE REDESIGN CAN RENAME.
   *
   * It used to be `.calculator-grid` — the legacy wrapper. The BEM rewrite renamed
   * that to `.calculator__fields`, so this returned early, no listener was attached,
   * and the whole results panel sat at "0,00 € / —" while the markup looked perfect.
   * Nothing threw. Exactly the silent failure mode this module's ids were protected
   * against, introduced by the one line that was not an id.
   *
   * Now keyed on an ID the id-contract check covers, so the same mistake cannot
   * recur unnoticed.
   */
  if (!document.getElementById('mensalidade')) return;

  initCalculatorHelp();
  initCalculatorTips();
  initCalculatorCta();

  /* ═══ block 1 — inputs, sliders, steppers ═══ */
  (function () {
    /* ========= Formatting & parsing helpers ========= */
    function formatEuro(value) {
      const number = Number(value);
      if (isNaN(number)) return "";
      return number.toLocaleString("en-US") + " €";
    }
    function formatYears(value) {
      const number = Number(value);
      if (isNaN(number)) return "";
      return number.toLocaleString("en-US") + " років";
    }
    function unformatEuro(value) {
      return String(value).replace(/[^\d.-]/g, "");
    }
    function parseMoney(v) {
      const n = parseFloat(String(v).replace(/[^\d.-]/g, ""));
      return isNaN(n) ? 0 : n;
    }
  
    /* ========= Input <-> Range sync ========= */
    function syncFormattedInput(rangeId, inputId) {
      const range = document.getElementById(rangeId);
      const input = document.getElementById(inputId);
      if (!range || !input) return;
  
      const updateFormatted = () => {
        let raw = unformatEuro(input.value || "0");
        let num = parseFloat(raw);
        if (!isNaN(num)) {
          if (!isNaN(parseFloat(input.min)) && num < parseFloat(input.min)) num = parseFloat(input.min);
          if (!isNaN(parseFloat(input.max)) && num > parseFloat(input.max)) num = parseFloat(input.max);
          input.value = formatEuro(num);
          range.value = num;
        }
      };
      updateFormatted();
  
      range.addEventListener("input", () => {
        input.value = formatEuro(range.value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
  
      input.addEventListener("focus", () => { input.value = unformatEuro(input.value); });
      input.addEventListener("blur", updateFormatted);
  
      input.addEventListener("input", () => {
        let raw = unformatEuro(input.value);
        let num = parseFloat(raw);
        if (!isNaN(num)) {
          if (!isNaN(parseFloat(input.min)) && num < parseFloat(input.min)) num = parseFloat(input.min);
          if (!isNaN(parseFloat(input.max)) && num > parseFloat(input.max)) num = parseFloat(input.max);
          range.value = num;
        }
      });
    }
  
    function syncYearsInput(rangeId, inputId) {
      const range = document.getElementById(rangeId);
      const input = document.getElementById(inputId);
      if (!range || !input) return;
  
      const updateFormatted = () => {
        let raw = unformatEuro(input.value || "0");
        let num = parseFloat(raw);
        if (!isNaN(num)) {
          if (!isNaN(parseFloat(input.min)) && num < parseFloat(input.min)) num = parseFloat(input.min);
          if (!isNaN(parseFloat(input.max)) && num > parseFloat(input.max)) num = parseFloat(input.max);
          input.value = formatYears(num);
          range.value = num;
        }
      };
      updateFormatted();
  
      range.addEventListener("input", () => {
        input.value = formatYears(range.value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
  
      input.addEventListener("focus", () => { input.value = unformatEuro(input.value); });
      input.addEventListener("blur", updateFormatted);
  
      input.addEventListener("input", () => {
        let raw = unformatEuro(input.value);
        let num = parseFloat(raw);
        if (!isNaN(num)) {
          if (!isNaN(parseFloat(input.min)) && num < parseFloat(input.min)) num = parseFloat(input.min);
          if (!isNaN(parseFloat(input.max)) && num > parseFloat(input.max)) num = parseFloat(input.max);
          range.value = num;
        }
      });
    }
  
    function syncPlainInput(rangeId, inputId) {
      const range = document.getElementById(rangeId);
      const input = document.getElementById(inputId);
      if (!range || !input) return;
  
      range.addEventListener("input", () => {
        input.value = range.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
  
      input.addEventListener("blur", () => {
        enforceMinMax(input);
        range.value = input.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      });
  
      input.addEventListener("input", () => {
        enforceMinMax(input);
        range.value = input.value;
      });
    }
  
    function enforceMinMax(input) {
      if (!input) return;
      let value = parseFloat(String(input.value).replace(/[^\d.-]/g, ''));
      const min = parseFloat(input.min);
      const max = parseFloat(input.max);
      if (isNaN(value)) return;
      if (!isNaN(min) && value < min) input.value = min;
      else if (!isNaN(max) && value > max) input.value = max;
    }
  
    /* Apply sync to all fields/sliders */
    syncFormattedInput("property-price-range", "property-price-input");
    syncFormattedInput("loan-amount-range", "loan-amount-input"); // down payment
    syncYearsInput("loan-term-range", "loan-term-input");
    syncFormattedInput("salary-range", "salary-input");
    syncFormattedInput("net-income-range", "net-income-input");
    syncFormattedInput("expenses-range", "expenses-input");
  
    /* ========= Property Price ⇢ Down Payment MAX (hard cap at price - step, but ≤ 1,000,000) ========= */
    function updateDownPaymentMax() {
      const propertyPriceInput = document.getElementById("property-price-input");
      const propertyPriceRange = document.getElementById("property-price-range");
      const loanAmountInput = document.getElementById("loan-amount-input");
      const loanAmountRange = document.getElementById("loan-amount-range");
      if (!propertyPriceInput || !propertyPriceRange || !loanAmountInput || !loanAmountRange) return;
  
      const price = parseFloat((typeof unformatEuro === 'function' ? unformatEuro(propertyPriceInput.value) : String(propertyPriceInput.value).replace(/[^\d.-]/g, ''))) || 0;
      const step = parseInt(loanAmountRange.step || loanAmountInput.step || 5000, 10) || 5000;
      const ABS_MAX = 1000000;
      const dynamicCap = Math.max(0, price - step);
      const maxDownPayment = Math.min(ABS_MAX, dynamicCap);
  
      loanAmountRange.max = String(maxDownPayment);
      loanAmountInput.max = String(maxDownPayment);
  
      const currentInputVal = parseFloat((typeof unformatEuro === 'function' ? unformatEuro(loanAmountInput.value) : String(loanAmountInput.value).replace(/[^\d.-]/g, ''))) || 0;
      const currentRangeVal = parseFloat(loanAmountRange.value) || 0;
  
      const floorToStep = (val) => {
        if (val > maxDownPayment) {
          return Math.floor(maxDownPayment / step) * step;
        }
        return val;
      };
  
      const newVal = floorToStep(Math.max(currentInputVal, currentRangeVal));
  
      if (typeof formatEuro === 'function') {
        loanAmountInput.value = formatEuro(newVal);
      } else {
        loanAmountInput.value = newVal.toLocaleString("en-US") + " €";
      }
      loanAmountRange.value = String(newVal);
  
      loanAmountInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
  
    (function wirePriceToDownPayment() {
      const propertyPriceInput = document.getElementById("property-price-input");
      const propertyPriceRange = document.getElementById("property-price-range");
      if (propertyPriceInput) {
        propertyPriceInput.addEventListener('input', updateDownPaymentMax);
        propertyPriceInput.addEventListener('blur', updateDownPaymentMax);
      }
      if (propertyPriceRange) {
        propertyPriceRange.addEventListener('input', updateDownPaymentMax);
        propertyPriceRange.addEventListener('change', updateDownPaymentMax);
      }
      updateDownPaymentMax();
    })();
  
    /* ========= Net Income MAX should not exceed Salary ========= */
    function updateNetIncomeMax() {
      const salaryInput = document.getElementById("salary-input");
      const salaryRange = document.getElementById("salary-range");
      const netIncomeInput = document.getElementById("net-income-input");
      const netIncomeRange = document.getElementById("net-income-range");
      if (!salaryInput || !salaryRange || !netIncomeInput || !netIncomeRange) return;
  
      const salary = parseFloat((typeof unformatEuro === 'function' ? unformatEuro(salaryInput.value) : String(salaryInput.value).replace(/[^\d.-]/g, ''))) || 0;
      const step = parseInt(netIncomeRange.step || netIncomeInput.step || 500, 10) || 500;
  
      // Update max attributes to reflect current salary
      netIncomeRange.max = String(salary);
      netIncomeInput.max = String(salary);
  
      // Determine a safe current value within new max
      const currentInputVal = parseFloat((typeof unformatEuro === 'function' ? unformatEuro(netIncomeInput.value) : String(netIncomeInput.value).replace(/[^\d.-]/g, ''))) || 0;
      const currentRangeVal = parseFloat(netIncomeRange.value) || 0;
  
      const floorToStep = (val) => {
        if (val > salary) {
          return Math.floor(salary / step) * step;
        }
        return val;
      };
  
      const newVal = floorToStep(Math.max(currentInputVal, currentRangeVal));
  
      if (typeof formatEuro === 'function') {
        netIncomeInput.value = formatEuro(newVal);
      } else {
        netIncomeInput.value = newVal.toLocaleString("en-US") + " €";
      }
      netIncomeRange.value = String(newVal);
  
      netIncomeInput.dispatchEvent(new Event('input', { bubbles: true }));
    }
  
    (function wireSalaryToNetIncome() {
      const salaryInput = document.getElementById("salary-input");
      const salaryRange = document.getElementById("salary-range");
      const netIncomeInput = document.getElementById("net-income-input");
      const netIncomeRange = document.getElementById("net-income-range");
  
      if (salaryInput) {
        salaryInput.addEventListener('input', updateNetIncomeMax);
        salaryInput.addEventListener('blur', updateNetIncomeMax);
      }
      if (salaryRange) {
        salaryRange.addEventListener('input', updateNetIncomeMax);
        salaryRange.addEventListener('change', updateNetIncomeMax);
      }
      if (netIncomeInput) {
        // Ensure clamping on manual edits once focus leaves the field
        netIncomeInput.addEventListener('blur', updateNetIncomeMax);
      }
      if (netIncomeRange) {
        // Ensure clamping when dragging the range beyond salary and releasing
        netIncomeRange.addEventListener('change', updateNetIncomeMax);
      }
  
      updateNetIncomeMax();
    })();
  
    /* ========= Rate controls — two options (variable | fixed) ========= */
    (function rateControlsInit() {
      const radios = document.querySelectorAll('input[name="rate-type"]');
  
      const variableStepper = document.getElementById('variable-stepper');
      const fixedStepper = document.getElementById('fixed-stepper');
  
      const variableInput = document.getElementById('variable-rate-input');
      const fixedInput = document.getElementById('fixed-rate-input');
  
      /*
       * ⚠️ `manual` is the THIRD rate mode, added 2026-08-05 on Petr's instruction. It
       * exists in the Figma frame and had never been implemented anywhere, production
       * included. It must be registered in BOTH maps: `steppers` drives which control is
       * shown, `inputs` is what emitActiveRate() reads. Registering it in only one gives
       * a radio that reveals its stepper and then reports a rate of 0.
       */
      const manualStepper = document.getElementById('manual-stepper');
      const manualInput = document.getElementById('manual-rate-input');

      const steppers = { variable: variableStepper, fixed: fixedStepper, manual: manualStepper };
      const inputs = { variable: variableInput, fixed: fixedInput, manual: manualInput };
  
      const state = {
        activeRateType: (document.querySelector('input[name="rate-type"]:checked') || { value: 'variable' }).value
      };
  
      function emitActiveRate() {
        const v = parseFloat(inputs[state.activeRateType]?.value || '0') || 0;
        document.dispatchEvent(new CustomEvent('mortgage:rate', {
          bubbles: true,
          detail: { rateType: state.activeRateType, rate: v }
        }));
      }
  
      function maybeEmit(name) {
        if (name !== state.activeRateType) return;
        emitActiveRate();
      }
  
      function sanitizePercentInput(inp) {
        let v = parseFloat(String(inp.value).replace(',', '.'));
        if (isNaN(v)) v = 0;
        const min = parseFloat(inp.min || '0') || 0;
        const max = parseFloat(inp.max || '100') || 100;
        v = Math.max(min, Math.min(max, v));
        inp.value = v.toFixed(2);
      }
  
      function showStepper(name) {
        Object.keys(steppers).forEach(k => {
          const st = steppers[k];
          const inp = inputs[k];
          if (!st || !inp) return;
          const isActive = (k === name);
          st.classList.toggle('hidden', !isActive);
          inp.disabled = !isActive;
          if (isActive) sanitizePercentInput(inp);
        });
        inputs[name]?.dispatchEvent(new Event('input', { bubbles: true }));
      }
  
      // Initial view + emit
      showStepper(state.activeRateType);
  
      // Radios: switch active source
      radios.forEach(r => r.addEventListener('change', () => {
        if (!r.checked) return;
        state.activeRateType = r.value; // 'variable' | 'fixed'
        showStepper(state.activeRateType);
        emitActiveRate();
      }));
  
      // Inputs: validate and emit when active
      Object.entries(inputs).forEach(([name, inp]) => {
        if (!inp) return;
  
        inp.min = "0";
        inp.max = "100";
        inp.step = inp.step || "0.01";
  
        inp.addEventListener('input', () => {
          let v = parseFloat(inp.value);
          if (!isNaN(v)) {
            if (v > parseFloat(inp.max)) inp.value = inp.max;
            else if (v < parseFloat(inp.min)) inp.value = inp.min;
          }
          maybeEmit(name);
        });
  
        inp.addEventListener('blur', () => {
          sanitizePercentInput(inp);
          maybeEmit(name);
        });
  
        inp.addEventListener('keydown', (ev) => {
          const allowed = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter'];
          if (allowed.includes(ev.key)) return;
          if (/^[0-9.\,]$/.test(ev.key)) return;
          ev.preventDefault();
        });
      });
  
      // Stepper buttons.
      //
      // ⚠️ Keyed on `.calculator__stepper`, NOT the legacy `.rate-stepper` — the legacy
      // class was dropped from the markup on 2026-08-06 because it dragged a whole
      // stylesheet with it (see the note in _calculator.scss). If you reintroduce
      // `.rate-stepper` anywhere, you reintroduce a 24px-tall control with 24px round
      // buttons and an 8px value on mobile.
      document.querySelectorAll('.calculator__stepper').forEach(stepper => {
        const plus = stepper.querySelector('.rate-plus');
        const minus = stepper.querySelector('.rate-minus');
        const input = stepper.querySelector('input');
        if (!input) return;
  
        if (plus) plus.addEventListener('click', () => {
          const max = parseFloat(input.max || 100);
          let val = parseFloat(input.value) || 0;
          val = Math.min(max, +(val + (parseFloat(input.step) || 0.01)).toFixed(2));
          input.value = val.toFixed(2);
          input.dispatchEvent(new Event('input', { bubbles: true }));
        });
  
        if (minus) minus.addEventListener('click', () => {
          const min = parseFloat(input.min || 0);
          let val = parseFloat(input.value) || 0;
          val = Math.max(min, +(val - (parseFloat(input.step) || 0.01)).toFixed(2));
          input.value = val.toFixed(2);
          input.dispatchEvent(new Event('input', { bubbles: true }));
        });
      });
  
      // Public API
      window.getSelectedRate = function () {
        const type = state.activeRateType;
        const rate = parseFloat(inputs[type]?.value || '0') || 0;
        return { type, rate };
      };
  
      // Fire once on load
      emitActiveRate();
    })();
  
    /* ========= Mobile class toggle ========= */
    (function () {
      const mq = window.matchMedia('(max-width: 480px)');
      const apply = () => document.documentElement.classList.toggle('calc-mobile', mq.matches);
      if (mq.addEventListener) mq.addEventListener('change', apply); else mq.addListener(apply);
      apply();
    })();
  
  })();

  /* ═══ block 2 — results, DSTI, IMT, Imposto do Selo ═══ */
  (function () {
      /* ---------- Helpers ---------- */
      function parseEuroString(value) {
        if (!value && value !== 0) return 0;
        try {
          const cleaned = String(value).replace(/[^\d+\-.]/g, '');
          const n = parseFloat(cleaned);
          return isNaN(n) ? 0 : n;
        } catch { return 0; }
      }
    
      /* ---------- DOM ---------- */
      const propertyPriceInput = document.getElementById("property-price-input");
      const downPaymentInput   = document.getElementById("loan-amount-input");
      const loanTermInput      = document.getElementById("loan-term-input");
    
      const netIncomeInput  = document.getElementById("net-income-input");
      const expensesInput   = document.getElementById("expenses-input");
    
      const rateTypeRadios  = document.querySelectorAll('input[name="rate-type"]');
      const variableRateInp = document.querySelector('#variable-stepper input');
      const fixedRateInp    = document.querySelector('#fixed-stepper input');
      // Block 2 has its own refs — it is a separate IIFE from the mode-swap block above,
      // so `manual` has to be looked up here as well as there.
      const manualRateInp   = document.querySelector('#manual-stepper input');
    
      /* outputs */
      const mensalidadeEl = document.getElementById("mensalidade");
      const dstiPercentEl = document.getElementById("dsti-percent");
      const tanEl         = document.getElementById("tan");
      const indexanteEl   = document.getElementById("indexante");
      const spreadEl      = document.getElementById("spread");
      const impostoSeloEl = document.getElementById("imposto-selo");
      const ltvEl         = document.getElementById("ltv");
      const prazoEl       = document.getElementById("prazo");
      const montanteEl    = document.getElementById("montante");
      const imtContEl     = document.getElementById("imt-cont");
    
      /* gauge */
      const gaugeDot = document.getElementById("gauge-dot");
      const p1 = document.getElementById("path-green");
      const p2 = document.getElementById("path-yellow");
      const p3 = document.getElementById("path-red");
    
      /* ---------- Parameters ---------- */
      /*
       * ⚠️ WAS `const INDEXANTE_CONST = 0.02143;` — a Euribor snapshot hardcoded in
       * the page. Euribor moves and the constant did not, so the variable-rate
       * results drifted further from reality every month and only a developer could
       * correct them. That is the most likely explanation for the client's report
       * that the calculator started giving wrong numbers a few months ago.
       *
       * It now comes from an editable field (Page → Calculator → Indexante), with
       * the SAME 2.143 as the default, so nothing on the page moves today — the
       * number simply becomes correctable without a deploy.
       *
       * This does NOT resolve the separate DSTI problem documented below.
       */
      const INDEXANTE_CONST =
        (parseFloat(document.querySelector('.calculator')?.dataset.akIndexante) || 2.143) / 100;
    
      /* ---------- IMT calculation ---------- */
      function calculateIMTContinente(price) {
        const brackets = [
          { max: 104261,  rate: 0,     deduction: 0 },
          { max: 142618,  rate: 0.02,  deduction: 2085.22 },
          { max: 194458,  rate: 0.05,  deduction: 6363.76 },
          { max: 324058,  rate: 0.07,  deduction: 10252.92 },
          { max: 648022,  rate: 0.08,  deduction: 13493.50 },
          { max: 1128287, rate: 0.06,  deduction: 0 },
          { max: Infinity,rate: 0.075, deduction: 0 }
        ];
        const bracket = brackets.find(b => price <= b.max);
        return Math.max(0, price * (bracket?.rate || 0) - (bracket?.deduction || 0));
      }
    
      /* ---------- Main calculation ---------- */
      function updateCalculation() {
        const propertyPrice = parseEuroString(propertyPriceInput?.value);
        const downPayment   = parseEuroString(downPaymentInput?.value);
        const loanAmount    = Math.max(0, propertyPrice - downPayment);
    
        const termYears  = parseInt(loanTermInput?.value, 10) || 0;
        const termMonths = termYears * 12;
    
        // Rate selection
        const activeType = (document.querySelector('input[name="rate-type"]:checked')?.value) || 'fixed';
        let interestRate = 0, spread = 0, indexante = 0;
        let displayRate = 0; // Для відображення в UI
        let interestRateForDSTI = 0; // Для розрахунку DSTI (зі знижкою для фіксованої)
  
        if (activeType === 'fixed') {
          // Фіксована ставка
          const fixedRateUI = (parseFloat(fixedRateInp?.value) || 0) / 100;
          displayRate = fixedRateUI;
          interestRate = fixedRateUI; // Повна ставка для Mensalidade
          /*
           * ⚠️ WAS `fixedRateUI - INDEXANTE_CONST` — the client's "wrong result".
           *
           * That subtracted the Euribor index from the contract rate and computed
           * DSTI at what was left: 2.80% − 2.143% = 0.657%, a rate no borrower pays.
           * The page then contradicted itself in plain sight — showing a payment of
           * 925,89 € against an income of 2 500 € while reporting DSTI 30%, where
           * 925.89 / 2500 = 37.0%.
           *
           * Now the DSTI uses the SAME rate as the payment shown next to it. That is
           * the conservative fix and needs no regulatory judgement: it does not add a
           * stress increment, it only stops the page disagreeing with itself.
           *
           * ⏳ STILL OPEN, awaiting Anna: Banco de Portugal's macroprudential
           * recommendation expects an interest-rate SHOCK on top of the contract rate
           * for the affordability test (+1.5pp → 42%, +3.0pp → 48% on these inputs),
           * and the variable branch below applies none at all. Adding a specific
           * increment is a regulatory decision, not a code cleanup — so it is not
           * invented here. Whatever she answers, it changes this one line.
           */
          interestRateForDSTI = fixedRateUI;
        } else if (activeType === 'variable') {
          // Змінна ставка: беремо spread з поля вводу і додаємо до індексу
          indexante = INDEXANTE_CONST;
          spread    = (parseFloat(variableRateInp?.value) || 0) / 100;
          interestRate = indexante + spread;
          displayRate = interestRate;
          interestRateForDSTI = interestRate; // Однакова для DSTI
        } else if (activeType === 'manual') {
          /*
           * Вручну — the visitor types the final TAN and it is used as given: no index
           * added, no spread, no stress increment. Added 2026-08-05 with the third radio.
           *
           * ⚠️ WITHOUT THIS BRANCH THE THIRD MODE WOULD COMPUTE AT 0%. interestRate is
           * initialised to 0 above, so an unhandled activeType does not fail loudly — it
           * quietly reports a payment far below reality on a licensed intermediary's
           * calculator. That is exactly why the third radio was held back on 2026-07-31,
           * and it is why the radio and this branch must ship together.
           *
           * ⏳ This is arithmetically identical to the `fixed` branch, because "type the
           * TAN" is the only reading the design offers for «Вручну». Flagged for Petr:
           * if Фіксована is meant to be a PRESET rate instead, that is the change, and it
           * belongs here.
           */
          const manualRateUI = (parseFloat(manualRateInp?.value) || 0) / 100;
          displayRate = manualRateUI;
          interestRate = manualRateUI;
          interestRateForDSTI = manualRateUI;
        }
  
        // Mensalidade - з повною ставкою
        const monthlyRate  = interestRate / 12;
        const payment = (loanAmount > 0 && termMonths > 0)
          ? (monthlyRate === 0 ? loanAmount / termMonths :
             (loanAmount * monthlyRate) / (1 - Math.pow(1 + monthlyRate, -termMonths)))
          : 0;
  
        // Платіж для DSTI - зі знижкою для фіксованої
        const monthlyRateForDSTI = interestRateForDSTI / 12;
        const paymentForDSTI = (loanAmount > 0 && termMonths > 0)
          ? (monthlyRateForDSTI === 0 ? loanAmount / termMonths :
             (loanAmount * monthlyRateForDSTI) / (1 - Math.pow(1 + monthlyRateForDSTI, -termMonths)))
          : 0;
    
        mensalidadeEl.textContent = payment.toLocaleString("fr-FR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + " €";
  
        // Info blocks
        tanEl.textContent       = (displayRate * 100).toFixed(3) + "%"; // Показуємо введену ставку
        indexanteEl.textContent = (indexante   * 100).toFixed(3) + "%";
        spreadEl.textContent    = (spread      * 100).toFixed(3) + "%";
        /*
         * ⚠️ FLOOR, NOT ROUND — verified against Anna's reference simulator on two
         * cases she sent. 230 000 / 350 000 = 65.71% shows as 65 there (we rounded to
         * 66); 350 000 / 450 000 = 77.78% shows as 77 (we rounded to 78). Both point
         * the same way, and the direction matters: LTV is a bank threshold, so
         * rounding UP overstates it and can make a case look outside a limit it
         * actually meets.
         */
        ltvEl.textContent       = propertyPrice > 0 ? Math.floor((loanAmount / propertyPrice) * 100) + "%" : "0%";
        prazoEl.textContent     = termMonths + " Meses";
        montanteEl.textContent  = loanAmount.toLocaleString("fr-FR", { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + " €";
    
        /* ===== DSTI ===== */
        const netIncome  = parseEuroString(netIncomeInput?.value);
        const expenses   = parseEuroString(expensesInput?.value);
        
        // Розраховуємо доступний дохід просто як чистий дохід мінус витрати.
        const availableIncome = netIncome - expenses;
        
        // Розраховуємо DSTI на основі платежу зі знижкою для фіксованої.
        let dstiVal = availableIncome > 0 ? (paymentForDSTI / availableIncome) * 100 : 100;
        let dstiText = Math.round(Math.min(dstiVal, 100)) + "%";
        dstiPercentEl.textContent = dstiText;
    
        // Gauge
        if (gaugeDot && !isNaN(dstiVal)) {
          try {
            const l1 = p1.getTotalLength();
            const l2 = p2.getTotalLength();
            const l3 = p3.getTotalLength();
            const total = l1 + l2 + l3;
            const percent = Math.min(1, Math.max(0, (dstiVal / 100)));
            const targetLength = percent * total;
    
            let point;
            if (targetLength <= l1) point = p1.getPointAtLength(targetLength);
            else if (targetLength <= l1 + l2) point = p2.getPointAtLength(targetLength - l1);
            else point = p3.getPointAtLength(targetLength - l1 - l2);
    
            if (point) gaugeDot.setAttribute("transform", `translate(${point.x}, ${point.y})`);
          } catch {}
        }
    
        // IMT + Imposto Selo
        const imtCont = calculateIMTContinente(propertyPrice);
        imtContEl.textContent = imtCont.toLocaleString("fr-FR") + " €";
  
        const impostoSelo = Math.round(propertyPrice * 0.008);
        impostoSeloEl.textContent = impostoSelo.toLocaleString("fr-FR") + " €";
      }
    
      /* ---------- Events ---------- */
      const inputs = [
        propertyPriceInput, downPaymentInput, loanTermInput,
        netIncomeInput, expensesInput,
        variableRateInp, fixedRateInp
      ].filter(Boolean);
    
      inputs.forEach(el => el.addEventListener("input", updateCalculation));
      rateTypeRadios.forEach(r => r.addEventListener("change", updateCalculation));
    
      updateCalculation();
      setTimeout(updateCalculation, 200);
  })();
}

/**
 * The "Довідка" help panel.
 *
 * Replaces the Divi Popups section the old `<a href="#infobox">` pointed at — which
 * had already stopped working on production (measured there: a 0×0 element whose
 * click opened nothing). Same open/close contract as the mobile drawer, because it
 * is the one already proven in this theme: Escape closes, the scrim closes, focus
 * moves into the panel and returns to the trigger, and body scroll is locked while
 * it is open.
 */
function initCalculatorHelp() {
  const panel = document.querySelector('[data-ak-help]');
  const trigger = document.querySelector('[data-ak-help-open]');

  if (!panel || !trigger) return;

  const dialog = panel.querySelector('.calc-help__dialog');

  const open = () => {
    panel.hidden = false;
    requestAnimationFrame(() => panel.classList.add('is-open'));
    trigger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    (dialog.querySelector('button') || dialog).focus({ preventScroll: true });
  };

  const close = () => {
    panel.classList.remove('is-open');
    panel.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    trigger.focus({ preventScroll: true });
  };

  trigger.addEventListener('click', () => {
    if (trigger.getAttribute('aria-expanded') === 'true') close();
    else open();
  });

  panel.querySelectorAll('[data-ak-help-close]').forEach((el) => {
    el.addEventListener('click', close);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') close();
  });
}

/**
 * The 16 ⓘ help disclosures.
 *
 * The copy they show was ported out of Divi's page CSS, where it lived as
 * `content:` strings on `.tooltip:hover::after` — and where a media query hid every
 * one of them below 900px, because a hover tooltip cannot work on a touch screen.
 * That is why this is a BUTTON with `aria-expanded` and not a CSS hover effect: it
 * has to work on a phone, it has to be dismissible with Escape, and the panel has to
 * survive the pointer moving onto it (WCAG 2.2 §1.4.13). A `:hover` rule gives none
 * of the three.
 *
 * ⚠️ ALL FIVE ROUTES CONVERGE ON ONE PIECE OF STATE — click, hover, focus, Escape and
 * click-outside. That is why the open class is set here and never in CSS: a
 * `:hover` rule plus a JS class is two sources of truth for one boolean, and they
 * disagree the moment you click and then move the mouse away.
 */
function initCalculatorTips() {
  const tips = Array.from(document.querySelectorAll('[data-ak-tip]'));

  if (!tips.length) return;

  // Clicking PINS a panel open so it survives pointerleave; hovering only previews it.
  // Without the distinction, moving the mouse off a panel you deliberately opened
  // closes it, which on the longest tips (250 characters) means you cannot finish
  // reading one you opened on purpose.
  let pinned = null;

  /*
   * ⚠️ Escape closes the panel and returns focus to the button — and returning focus
   * fired the focus handler below, which reopened the panel it had just closed. The
   * test caught it: one panel still open with focus correctly restored.
   *
   * Consumed synchronously, because .focus() dispatches its event synchronously; it is
   * also cleared right after the call, since focusing an ALREADY-focused button fires
   * no event at all and the flag would otherwise swallow the next genuine focus.
   */
  let skipFocusOpen = false;

  const partsOf = (tip) => ({
    button: tip.querySelector('.calc-tip__button'),
    panel: tip.querySelector('.calc-tip__panel'),
  });

  const hide = (tip) => {
    const { button, panel } = partsOf(tip);
    if (!button || !panel) return;

    panel.classList.remove('calc-tip__panel--open');
    panel.style.transform = '';
    panel.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  };

  const hideAll = (except) => {
    tips.forEach((t) => {
      if (t !== except) hide(t);
    });

    if (pinned && pinned !== except) pinned = null;
  };

  const show = (tip) => {
    const { button, panel } = partsOf(tip);
    if (!button || !panel) return;

    // One at a time. Two open panels overlap each other in the metrics grid.
    hideAll(tip);

    /*
     * Presence first, fade a frame later — the same two-step as the «Довідка» panel.
     * The panel is `hidden` when closed so its 310px box leaves the flow entirely
     * (visibility alone kept the box and scrolled the document sideways), which means
     * it has to be un-hidden BEFORE it can be measured below.
     */
    panel.hidden = false;
    panel.style.transform = '';
    requestAnimationFrame(() => panel.classList.add('calc-tip__panel--open'));
    button.setAttribute('aria-expanded', 'true');

    /*
     * Keep the panel inside the viewport, measured AFTER it is visible — a hidden
     * element's getBoundingClientRect() is not the box it will occupy.
     *
     * ⚠️ THIS SHIFTS, IT DOES NOT FLIP, and the first attempt did flip. Swapping
     * `left: 0` for `right: 0` only works when the panel fits on the other side; at 375
     * the panel is 310 wide and neither side has room, so every one of the 16 hung off
     * an edge and the whole document scrolled sideways. Clamping to both edges is
     * correct at any width, which left/right alignment cannot be.
     *
     * It also replaces the original's hand-assigned `.tlt-right`, set on five badges by
     * eye and therefore wrong at every width its author did not open.
     */
    const margin = 12; // breathing room from the viewport edge, in px
    const box = panel.getBoundingClientRect();
    let dx = 0;

    if (box.right > window.innerWidth - margin) {
      dx = window.innerWidth - margin - box.right;
    }

    // Applied second so a panel too wide for the viewport pins to the LEFT edge and
    // starts at its first word, rather than being cut off at the beginning.
    if (box.left + dx < margin) {
      dx = margin - box.left;
    }

    if (dx) panel.style.transform = `translateX(${Math.round(dx)}px)`;
  };

  const isOpen = (tip) => partsOf(tip).button?.getAttribute('aria-expanded') === 'true';

  // Hover is a POINTER affordance only. On a touch screen `pointerenter` fires from the
  // tap itself, so the panel would open on hover and then be closed again by the click
  // handler in the same gesture — a badge that does nothing when tapped.
  const canHover = window.matchMedia('(hover: hover)').matches;

  tips.forEach((tip) => {
    const { button } = partsOf(tip);
    if (!button) return;

    button.addEventListener('click', () => {
      if (pinned === tip) {
        hide(tip);
        pinned = null;
      } else {
        show(tip);
        pinned = tip;
      }
    });

    // Keyboard: focus reveals, so a tabbing visitor is not told there is help and then
    // made to guess that Enter shows it.
    button.addEventListener('focus', () => {
      if (skipFocusOpen) return;
      if (!isOpen(tip)) show(tip);
    });

    button.addEventListener('blur', () => {
      if (pinned !== tip) hide(tip);
    });

    if (canHover) {
      // On the wrapper, not the button — the panel is inside it, so the pointer moving
      // from the badge onto the text never leaves. That is §1.4.13's "hoverable".
      tip.addEventListener('pointerenter', () => show(tip));
      tip.addEventListener('pointerleave', () => {
        if (pinned !== tip) hide(tip);
      });
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;

    const open = tips.find(isOpen);
    if (!open) return;

    // Escape inside the calculator must not also close the «Довідка» modal behind it.
    e.stopPropagation();

    const { button } = partsOf(open);
    hideAll(null);

    skipFocusOpen = true;
    button?.focus({ preventScroll: true });
    skipFocusOpen = false;
  });

  /*
   * A resize invalidates every shift computed above, and re-measuring 16 panels on a
   * resize stream is worse than the problem. Closing is also what the visitor expects:
   * on a phone, a resize is the on-screen keyboard or an orientation change.
   */
  window.addEventListener('resize', () => {
    if (tips.some(isOpen)) hideAll(null);
  });

  document.addEventListener('click', (e) => {
    if (!pinned) return;
    if (pinned.contains(e.target)) return;

    hideAll(null);
  });
}

/**
 * The head's «Розрахувати» button.
 *
 * ⚠️ The design specifies the button, not what it does — a Figma frame cannot. It
 * cannot mean "compute": the calculator recomputes on every keystroke, so a
 * submit-style action would be a control that looks like it does something and
 * does not. So it moves the visitor to the first field, which is a real job on a
 * phone where the head and the fields are a screen apart.
 *
 * `preventScroll` then an explicit `scrollIntoView` on the CARD, not the input:
 * focusing the input alone scrolls it to the viewport edge and the label above it
 * ends up off-screen, so the field arrives with no idea what it is asking for.
 */
function initCalculatorCta() {
  const cta = document.querySelector('[data-ak-calc-focus]');
  const field = document.getElementById('property-price-input');
  const card = document.querySelector('.calculator__layout');

  if (!cta || !field) return;

  cta.addEventListener('click', () => {
    (card || field).scrollIntoView({ behavior: 'smooth', block: 'start' });
    field.focus({ preventScroll: true });
  });
}
