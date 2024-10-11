<p>Before you can subscribe to an Option contract, you must configure the underlying Future and get the contract <code>Symbol</code>.</p>

<div class="section-example-container">
    <pre class="csharp">public class BasicFutureOptionAlgorithm : QCAlgorithm
{
    private Future _future;
    private Symbol _contractSymbol;
    public override void Initialize()
    {
        SetStartDate(2020,1,1);
        _future = AddFuture(Futures.Indices.SP500EMini,
            extendedMarketHours: true,
            dataMappingMode: DataMappingMode.OpenInterest,
            dataNormalizationMode: DataNormalizationMode.BackwardsRatio,
            contractDepthOffset: 0);
        _future.SetFilter(0, 182);
    }

    public override void OnData(Slice data)
    {
        if (_contractSymbol == null)
        {
            // Method 1: Add the desired option chain for the mapped contract
            _contractSymbol = SelectOptionContract(_future.Mapped);
            AddFutureOptionContract(_contractSymbol);
            
            // Method 2: Get all future contracts from the Future chain provider
            var futureContractSymbol = FutureChainProvider.GetFutureContractList(_future.Symbol, Time)
                .OrderBy(symbol =&gt; symbol.ID.Date).FirstOrDefault();
            var symbol = SelectOptionContract(futureContractSymbol);
            AddFutureOptionContract(symbol);
            
            // Method 3: Iterate all Future chains to find the desired future options contracts  
            foreach (var (_, chain) in data.FutureChains)
            {
                var expiry = chain.Min(contract =&gt; contract.Expiry);
                var futureContract = chain.Where(contract =&gt; contract.Expiry == expiry).FirstOrDefault();
                symbol = SelectOptionContract(futureContract.Symbol);
                AddFutureOptionContract(symbol);
            }
        }
    }

    private Symbol SelectOptionContract(Symbol futureContractSymbol)
    {
        var chain = OptionChain(futureContractSymbol)
            .Where(contract =&gt; contract.Right == OptionRight.Call).ToList();
        var expiry = chain.Min(contract =&gt; contract.Expiry);
        return chain
            .Where(contract =&gt; contract.Expiry == expiry)
            .OrderBy(contract =&gt; contract.Strike)
            .Select(contract =&gt; contract.Symbol).FirstOrDefault();
    }
}</pre>
    <pre class="python">class BasicFutureOptionAlgorithm(QCAlgorithm):
    def initialize(self):
        self.set_start_date(2020, 1, 1)
        self._future = self.add_future(
            Futures.Indices.SP_500_E_MINI,
            extended_market_hours=True,
            data_mapping_mode=DataMappingMode.OPEN_INTEREST,
            data_normalization_mode=DataNormalizationMode.BACKWARDS_RATIO,
            contract_depth_offset=0
        )
        self._future.set_filter(0, 182)
        self._contract_symbol = None
    
    def on_data(self, data):
        if not self._contract_symbol:
            # Method 1: Add the desired option chain for the mapped contract
            self._contract_symbol = self._select_option_contract(self._future.mapped)
            self.add_future_option_contract(self._contract_symbol)

            # Method 2: Get all future contracts from the Future chain provider
            future_contract_symbols = self.future_chain_provider.get_future_contract_list(self._future.symbol, self.time)
            future_contract_symbol = sorted(future_contract_symbols, key=lambda symbol: symbol.id.date)[0]
            symbol = self._select_option_contract(future_contract_symbol)
            self.add_future_option_contract(symbol)

            # Method 3: Iterate all Future chains to find the desired future options contracts         
            for symbol, chain in data.future_chains.items():
                expiry = min([contract.expiry for contract in chain])
                future_contract = next(contract for contract in chain if contract.expiry == expiry)
                symbol = self._select_option_contract(future_contract.symbol)
                self.add_future_option_contract(symbol)

    def _select_option_contract(self, future_contract_symbol):
        chain = self.option_chain(future_contract_symbol)
        chain = [contract for contract in chain if contract.right == OptionRight.CALL]
        expiry = min([contract.expiry for contract in chain])
        chain = [contract for contract in chain if contract.expiry == expiry]
        return sorted(chain, key=lambda contract: contract.strike)[0].symbol</pre>
</div>

<h4>Configure the Underlying Futures Contracts</h4>

<p>In most cases, you should <a href='/docs/v2/writing-algorithms/securities/asset-classes/futures/requesting-data/individual-contracts#02-Create-Subscriptions'>subscribe to the underlying Futures contract</a> before you subscribe to a Futures Option contract.</p>

<div class="section-example-container">
    <pre class="csharp">_future = AddFuture(Futures.Indices.SP500EMini,
    extendedMarketHours: true,
    dataMappingMode: DataMappingMode.OpenInterest,
    dataNormalizationMode: DataNormalizationMode.BackwardsRatio,
    contractDepthOffset: 0);</pre>
    <pre class="python">self._future = self.add_future(
    Futures.Indices.SP_500_E_MINI,
    extended_market_hours=True,
    data_mapping_mode=DataMappingMode.OPEN_INTEREST,
    data_normalization_mode=DataNormalizationMode.BACKWARDS_RATIO,
    contract_depth_offset=0
)</pre>
</div>

<p>To get a <code>Symbol</code> of a specific Futures contract, use the <code class="csharp">Mapped</code><code class="python">mapped</code> property of the <code>Future</code> object or the <code class="csharp">Symbol</code><code class="python">symbol</code> property of the <code>FutureContract</code> objects in the <code class="csharp">Slice.FutureChains</code><code class="python">Slice.future_chains</code> collection.</p>

<h4>Get Contract Symbols</h4>

<p>
    To subscribe to a Future Option contract, you need the contract <code>Symbol</code>. 
    The preferred method to getting Option contract <code>Symbol</code> objects is to use the <code class="csharp">OptionChain</code><code class="python">option_chain</code> method. 
    <span class='python'>
        This method returns a <code>DataHistory[OptionUniverse]</code> object, which you can format into a DataFrame or iterate through.
        Each row in the DataFrame and each <code>OptionUniverse</code> object represents a single Future Option contract.
    </span>
    <span class='csharp'>This method returns a collection of <code>OptionUniverse</code> objects, where each object represents a Future Option contract.</span>
    Sort and filter the data to find the specific contract(s) you want to trade.
</p>

<div class="section-example-container">
    <pre class="csharp">var chain = OptionChain(futureContractSymbol)
    .Where(contract =&gt; contract.ID.OptionRight == OptionRight.Call).ToList();
var expiry = chain.Min(contract =&gt; contract.ID.Date);
_contractSymbol = chain
    .Where(contract =&gt; contract.ID.Date == expiry)
    .OrderBy(contract =&gt; contract.ID.StrikePrice)
    .Select(contract =&gt; contract.Symbol).FirstOrDefault();</pre>
    <pre class="python">chain = self.option_chain(future_contract_symbol)
chain = [contract for contract in chain if contract.id.option_right == OptionRight.CALL]
expiry = min([contract.id.date for contract in chain])
chain = [contract for contract in chain if contract.id.date == expiry]
self._contract_symbol = sorted(chain, key=lambda contract: contract.id.strike_price)[0].symbol</pre>
</div>

<p><code>OptionUniverse</code> objects have the following properties:</p>
<div data-tree='QuantConnect.Data.UniverseSelection.OptionUniverse'></div>

<h4>Subscribe to Contracts</h4>

<p>To create a Future Option contract subscription, pass the contract <code>Symbol</code> to the <code class="csharp">AddFutureOptionContract</code><code class="python">add_future_option_contract</code>  method. Save a reference to the contract <code class="csharp">Symbol</code><code class="python">symbol</code> so you can easily access the Option contract in the <a href="/docs/v2/writing-algorithms/securities/asset-classes/equity-options/handling-data#04-Option-Chains">OptionChain</a> that LEAN passes to the <code class="csharp">OnData</code><code class="python">on_data</code> method. This method returns an <code>Option</code> object. To override the default <a href="/docs/v2/writing-algorithms/reality-modeling/options-models/pricing">pricing model</a> of the Option, <a href='https://www.quantconnect.com/docs/v2/writing-algorithms/reality-modeling/options-models/pricing#03-Set-Models'>set a pricing model</a>.</p>

<div class="section-example-container">
    <pre class="csharp">var option = AddFutureOptionContract(_contractSymbol);
option.PriceModel = OptionPriceModels.BinomialCoxRossRubinstein();</pre>
    <pre class="python">option = self.add_future_option_contract(self._contract_symbol)
option.price_model = OptionPriceModels.binomial_cox_ross_rubinstein()</pre>
</div>

<h4>Warm Up Contract Prices</h4>

<p>If you subscribe to a Future Option contract with <code class="csharp">AddFutureOptionContract</code><code class="python">add_future_option_contract</code>, you'll need to wait until the next <code>Slice</code> to receive data and trade the contract. To trade the contract in the same time step you subscribe to the contract, set the current price of the contract in a <a href='/docs/v2/writing-algorithms/initialization#07-Set-Security-Initializer'>security initializer</a>.</p>

<div class="section-example-container">
    <pre class="csharp">var seeder = new FuncSecuritySeeder(GetLastKnownPrices);
SetSecurityInitializer(new BrokerageModelSecurityInitializer(BrokerageModel, seeder));</pre>
    <pre class="python">seeder = FuncSecuritySeeder(self.get_last_known_prices)
self.set_security_initializer(BrokerageModelSecurityInitializer(self.brokerage_model, seeder))</pre>
</div>

<h4>Supported Assets</h4>

<p>To view the supported assets in the US Future Options dataset, see the <a href="/datasets/algoseek-us-future-options">Supported Assets</a>.</p>
